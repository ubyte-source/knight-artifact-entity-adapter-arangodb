<?PHP

namespace ArangoDB\operations\common\choose;

use stdClass;

use Entity\Map as Entity;

use ArangoDB\Parser;
use ArangoDB\Statement;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\common\Choose;
use ArangoDB\operations\common\choose\strict\Condition;
use ArangoDB\operations\common\choose\strict\Hop;

/* This code is a trait that is used to add strictness to the query. */

trait Strict
{
    protected $strict = true; // (bool)

    /**
     * *This function sets the strict mode of the class.*
     * 
     * The `useStrict()` function is used to set the strict mode of the class. 
     * 
     * @param bool strict If true, the validator will throw an exception if the value does not match
     * the schema. If false, the validator will return false if the value does not match the schema.
     * 
     * @return The object itself.
     */
    
    public function useStrict(bool $strict = true) : self
    {
        $this->strict = $strict;
        return $this;
    }

    /**
     * Returns the value of the strict property
     * 
     * @return The value of the strict property.
     */
    
    protected function getStrict() : bool
    {
        return $this->strict;
    }

    /**
     * * For each route in the parser, create a new object and add it to the response array.
     * * For each document in the route, create a new object and add it to the response array.
     * 
     * @param Parser parser The parser object.
     * @param Statement statement The statement to be executed.
     * 
     * @return The `getHop` method returns an array of `Hop` objects.
     */
    
    protected function getHop(Parser $parser, Statement $statement) : array
    {
        $first = $this->getCore()->begin();
        $response = [];
        if (null === $first) return $response;

        $routes = $parser->getRoutes();
        foreach ($routes as $route) {
            $documents = $route->getDocuments();
            $traversal = new stdClass();
            array_push($response, $traversal);
            foreach ($documents as $item => $document) {
                $type = $document->getEntity()->getType();
                if (false === property_exists($traversal, $type)) $traversal->$type = array();

                array_push($traversal->$type, new Hop($document));

                $continue = $first->getHash() === $document->getEntity()->getHash() || $type === Edge::TYPE;
                if (false === static::shouldSearch($this->getCore())
                    || $continue === false) continue;

                $keys = $document->getValues();
                $keys = array_keys($keys);
                array_walk($keys, function (string $name) use ($statement, $document) {
                    $keys = $document->getEntity()->getAllFieldsUniqueGroups();
                    $keys = array_key_exists(Edge::TYPE, $keys) ? $keys[Edge::TYPE] : array();
                    $keys = array_slice($keys, 0, 2);
                    if (Edge::TYPE === $document->getEntity()->getType()) {
                        $directorate = $document->getEntity()->getForceDirection() ?? $document->getEntity()->getDirection();
                        if (Edge::INBOUND === $directorate) $keys = array_reverse($keys, false);
                    }

                    if (Edge::TYPE === $document->getEntity()->getType() && false === in_array($name, $keys)
                        || array_key_exists(1, $keys) && $name === $keys[1]) return;

                    $start = $this->getPointer('start');
                    $start .= chr(46);
                    $start .= Edge::TYPE !== $document->getEntity()->getType() || $name !== $keys[0] ? $name : Arango::ID;
                    $document->setValue($name, $start);
                    $statement->pushSkipValues($start);
                });
            }
        }

        return $response;
    }

    /**
     * * For each route in the container, create a condition object.
     * * For each hop in the route, create a statement object.
     * * For each hop in the route, create a match object.
     * 
     * @param string type the type of the route, either "match" or "filter"
     * 
     * @return The `getExpressionRoutes` method returns an array of `Condition` objects.
     */
    
    protected function getExpressionRoutes(string $type, stdClass ...$container_match_routes)
    {
        $response = [];
        foreach ($container_match_routes as $route) {
            $condition = new Condition();
            array_push($response, $condition);
            array_walk($route->$type, function (Hop $hop, int $index) use ($condition) {
                $condition->addHops($hop);
                $condition->addMatches(count($hop->getDocument()->getValues()));

                $iteration = $hop->getDocument()->getEntity()->getType();
                $iteration = $this->getTypeIteration($iteration);

                $statement = new Statement();
                $specifics = 'IS_SAME_COLLECTION(' . $hop->getDocument()->getEntity()->getCollectionName() . chr(44) . chr(32) . $iteration . ')';
                $statement->append($specifics);
                $condition->addStatement($statement);

                $matches = $this->matches($iteration, $statement, $hop->getDocument());
                if (empty($matches)) return;
                
                $statement->append('AND');
                $specifics = implode($matches);
                $statement->append($specifics);

                $deterministic = $hop->getDocument()->getValues();
                $deterministic = array_keys($deterministic);
                $condition->setDeterministic(0 !== count($deterministic));;

                if (0 !== $index) return;

                $clean = [];
                array_push($clean, $hop->getDocument()->getEntity()->getField(Arango::KEY)->getName());
                array_push($clean, $hop->getDocument()->getEntity()->getField(Arango::ID)->getName());
                if (Edge::TYPE === $hop->getDocument()->getEntity()->getType()) {
                    $field = Edge::INBOUND === $hop->getDocument()->getEntity()->getDirection() ? Edge::TO : Edge::FROM;
                    array_push($clean, $field);
                }
                $deterministic = array_diff($deterministic, $clean);
                $condition->setDeterministic(0 !== count($deterministic));
            });
        }

        return $response;
    }

    /**
     * Get the pointer to the iteration of the specified type
     * 
     * @param string type The type of the parameter.
     * 
     * @return The pointer to the iteration of the type.
     */
    
    protected function getTypeIteration(string $type) : string
    {
        $iteration = $type . chr(69);
        return $this->getPointer($iteration);
    }

    /**
     * This function is responsible for defining the type of route 
     * designed by the developer
     * 
     * @param Parser parser The parser object.
     * @param Statement statement The statement to add the condition to.
     * 
     * @return The result of the query.
     */
    
    protected function addStrict(Parser $parser, Statement $statement) : void
    {  
        $hops = $this->getHop($parser, $statement);
        $hops_type = array_map(function (stdClass $traversal) {
            return array_keys((array)$traversal);
        }, $hops);
        $hops_type = call_user_func_array('array_merge', $hops_type);
        $hops_type = array_unique($hops_type, SORT_STRING);
        foreach ($hops_type as $type) {
            $conditions = static::getExpressionRoutes($type, ...$hops);
            $deterministic = array_map(function (Condition $condition) {
                return $condition->getDeterministic();
            }, $conditions);
            if (false === in_array(true, $deterministic, true)) continue;

            usort($conditions, function (Condition $a, Condition $b) {
                return array_sum($a->getMatches()) < array_sum($b->getMatches());
            });

            $statement_traversal = $type === Edge::TYPE ? Choose::TRAVERSAL_EDGE :  Choose::TRAVERSAL_VERTEX;
            $statement_traversal_check = $statement_traversal . chr(99);
            $statement_traversal_check = $this->getPointer($statement_traversal_check);
            $statement_traversal = $this->getPointer($statement_traversal);
            $statement->append('LET ' . $statement_traversal_check . ' = FLATTEN(', false);
            $statement->append('LET j = (', false);
            $statement->append('FOR ' . $this->getTypeIteration($type) . ' IN ' . $statement_traversal);

            $main = $math = $duplicate = array();
            array_walk($conditions, function (Condition $condition) use (&$main, &$math, &$duplicate, $statement) {
                $expressions = $condition->getStatements();
                $expressions = array_map(function (Statement $internal) use ($statement) {
                    return $statement->addFromStatement($internal);
                }, $expressions);
                $expressions_fingerprint = serialize($expressions);
                $expressions_fingerprint = md5($expressions_fingerprint);
                if (in_array($expressions_fingerprint, $duplicate)) return;

                array_push($duplicate, $expressions_fingerprint);
                array_push($main, $expressions);

                $hops = $condition->getHops();
                $hops = array_map(function (Hop $hop) {
                    return $hop->getHop();
                }, $hops);
                $hops = implode(chr(44) . chr(32), $hops);
                $hops = chr(91) . $hops . chr(93);
                array_push($math, $hops);
            });

            $main_optimization = call_user_func_array('array_merge', $main);
            $main_optimization = array_unique($main_optimization, SORT_STRING);
            $main_optimization = array_values($main_optimization);
            $main_optimization_key = array_map('md5', $main_optimization);
            $main_optimization = array_combine($main_optimization_key, $main_optimization);

            $prefix = chr(120);
            array_walk($main_optimization, function (string $value, string $hash) use ($statement, $prefix) {
                $statement->append('LET');
                $statement->append($prefix . $hash);
                $statement->append('=');
                $statement->append($value);
            });

            array_walk($main, function (array &$value) use ($prefix) {
                $value = array_map('md5', $value);
                $value = preg_filter('/^.*$/', $prefix . '$0', $value);
                $value = implode(chr(44) . chr(32), $value);
                $value = chr(91) . $value . chr(93);
            });

            $main = implode(chr(44), $main);
            $main = chr(91) . $main . chr(93);

            $statement->append('RETURN ' . $main, false);
            $statement->append(')');

            $math = implode(chr(44), $math);
            $math = chr(91) . $math . chr(93);

            $statement->append('LET d = (', false);
            $statement->append('LET h = ' . $math);
            $statement->append('LET c = COUNT(h)');
            $statement->append('FOR n IN 0..c - 1');
            $statement->append('LET v = j[*][n]');
            $statement->append('LET x = FIRST(', false);
            $statement->append('FOR z in v');
            $statement->append('FILTER !POSITION(z, true)');
            $statement->append('LIMIT 1');
            $statement->append('RETURN 1', false);
            $statement->append(')');
            $statement->append('FILTER x == null');
            $statement->append('LET b = (', false);
            $statement->append('LET m = COUNT(h[n])');
            $statement->append('FOR z IN 0..m - 1');
            $statement->append('LET l = FIRST(', false);
            $statement->append('FOR k IN v');
            $statement->append('FILTER k[z] == true');
            $statement->append('LIMIT 1');
            $statement->append('RETURN h[n][z]', false);
            $statement->append(')');
            $statement->append('RETURN l', false);
            $statement->append(')');
            $statement->append('FILTER !POSITION(b, null)');
            $statement->append('LET g = (', false);
            $statement->append('LET m = COUNT(b)');
            $statement->append('FOR z IN 0..m - 1');
            $statement->append('LET l = z - 1');
            $statement->append('FILTER z == 0 || 0 <= l AND b[l] != b[z]');
            $statement->append('RETURN b[z]', false);
            $statement->append(')');
            $statement->append('LET s = (', false);
            $statement->append('LET l = COUNT(g)');
            $statement->append('FOR z IN 0..l - 1');
            $statement->append('RETURN h[n][z] == g[z]', false);
            $statement->append(')');
            $statement->append('RETURN !POSITION(s, false)', false);
            $statement->append(')');
            $statement->append('RETURN d', false);
            $statement->append(')');
            $statement->append('FILTER POSITION(' . $statement_traversal_check . ', true)');
        }
    }

    /**
     * If the strict mode is enabled, and there are no documents in the route, then we should use
     * strict mode
     * 
     * @param Parser parser The parser object.
     * 
     * @return The `shouldUseStrict` method returns a boolean value.
     */
    
    protected function shouldUseStrict(Parser $parser) : bool
    {
        if (false === $this->getStrict()) return false;

        $edge = $parser->getTargetsEdge();
        $skip = $parser->getTargetsVertex();
        $skip = array_merge($skip, $edge);
        $skip = array_map(function (Entity $entity) {
            return $entity->getHash();
        }, $skip);

        $first = $this->getCore()->begin();
        array_push($skip, $first->getHash());

        $edges = $first->getContainer()->getEdges();
        if (is_array($edges)) array_walk($edges, function (Edge $edge) use (&$skip) {
            $document = new Document($edge);
            $document_unset = $edge->getForceDirection() ?? $edge->getDirection();
            $document_unset = $document_unset === Edge::INBOUND ? Edge::TO : Edge::FROM;
            $document->unsetFields($document_unset);
            if (empty($document->getValues())) array_push($skip, $edge->getHash());
        });

        $routes = $parser->getRoutes();
        foreach ($routes as $route) {
            $documents = $route->getDocuments();
            $documents = array_map(function (Document $document) use ($skip) {
                $hash = $document->getEntity()->getHash();
                return in_array($hash, $skip) ? null : $document->getValues();
            }, $documents);
            $documents = array_filter($documents);
            if (0 < count($documents)) return true;
        }

        return false;
    }
}
