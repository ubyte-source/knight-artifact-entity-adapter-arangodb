<?PHP

namespace ArangoDB\operations\common;

use Entity\Map as Entity;

use ArangoDB\Parser;
use ArangoDB\Initiator;
use ArangoDB\Statement;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\Shortestpath;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\common\Base;
use ArangoDB\operations\common\choose\Strict;
use ArangoDB\operations\common\choose\Limit;
use ArangoDB\operations\features\Match;

/* The Choose class is used to choose a vertex or edge from a collection */

abstract class Choose extends Base
{
    use Match, Strict;

    const EDGE = 'mie';
    const VERTEX = 'miv';

    const TRAVERSAL = 'mit';
    const TRAVERSAL_EDGE = 'mite';
    const TRAVERSAL_VERTEX = 'mitv';

    protected $with = true;           // (bool)
    protected $with_collections = []; // (array)
    protected $variable_start;        // (string)
    protected $limit;                 // Limit

    /**
     * The constructor for the PHP class is the same as the constructor for the C# class
     * 
     * @param Initiator core The core object that is used to access the database.
     */
    
    public function __construct(Initiator $core, ...$arguments)
    {
        parent::__construct($core, ...$arguments);
        $this->setLimit(new Limit());
    }

    /**
     * *This function is used to set the `with` property of the `QueryBuilder` class.*
     * 
     * The `with` property is used to determine whether or not to use the `WITH` clause in the query
     * 
     * @param bool with Whether or not to use the with keyword.
     * 
     * @return The object itself.
     */
    
    public function useWith(bool $with = true) : self
    {
        $this->with = $with;
        return $this;
    }

    /**
     * Returns an array of the collections that the current user has access to
     * 
     * @return An array of the collections that are being included in the query.
     */
    
    public function getWithCollections() : array
    {
        return $this->with_collections;
    }

    /**
     * This function returns an array of all the collections in the database
     * 
     * @return An array of strings.
     */
    
    public function getWithCollectionsParsed() : array
    {
        $parser = $this->getCore()->getStart();
        $parser = new Parser(...$parser);
        $parser = $parser->getWithCollections();
        $smooth = $this->getWithCollections();
        $smooth = array_merge($parser, $smooth);
        $smooth = array_unique($smooth, SORT_STRING);
        return $smooth;
    }

    /**
     * Add a collection to the list of collections to be included in the query
     * 
     * @return The object itself.
     */
    
    public function pushWithCollection(string ...$with_collections) : self
    {
        array_push($this->with_collections, ...$with_collections);
        return $this;
    }

    /**
     * Get the limit for the query
     * 
     * @return The limit object.
     */
    
    public function getLimit() : Limit
    {
        return $this->limit;
    }

    /**
     * This function executes the SQL statement and returns the results
     * 
     * @return The result of the executed Statement.
     */
    
    public function run() :? array
    {
        return $this->getStatement()->execute();
    }

    /**
     * This function creates a query for the traversal of the graph
     * 
     * @return The query is being returned.
     */
    
    public function getStatement() : Statement
    {
        $start = $this->getCore()->getStart();
        $parser = new Parser(...$start);
        $parser_target = $parser->getTargetsVertex();
        if (empty($parser_target) && method_exists($this, 'overwrite')) return $this->overwrite($parser, ...$start);

        $statement = new Statement();
        if (!!$skip = $this->getStatementSkipValues()) $statement->pushSkipValues(...$skip);

        $smooth = $this->getWith();
        if (true === $smooth) {
            $with = $this->getWithCollectionsParsed();
            $with = implode(chr(44) . chr(32), $with);
            $statement->append('WITH' . chr(32) . $with);
        }

        if (static::shouldSearch($this->getCore())) {
            $name = $this->getCore()->begin()->getCollectionName();
            $pointer = $this->getPointer('start');
            $statement->append('FOR');
            $statement->append($pointer);
            $statement->append('IN');
            $statement->append($name);

            $documents = array_map(function (Vertex $vertex) {
                return new Document($vertex);
            }, $start);
            if ($match = $this->matches($pointer, $statement, ...$documents)) {
                $match = implode(chr(32) . 'AND' . chr(32), $match);
                $match = 'FILTER' . chr(32) . $match;
                $statement->append($match);
            }
        } else {
            $id = $this->getCore()->begin()->getField(Arango::ID)->getValue();
            $this->setPointer('start', $id, $statement);
        }

        if (method_exists($this, 'main')) $this->main($parser, $statement);
        $edges = $parser->getEdgesNameWithDirection();
        $edges = implode(chr(44) . chr(32), $edges);
        $statement->append($edges);

        $this->setPrimaryConditions($parser, $statement);
        if (method_exists($this, 'manipulate')) $this->manipulate($parser, $statement);

        $return = $this->getReturn();
        $strict = $this->shouldUseStrict($parser);

        $traversal = $this->getPointer(static::TRAVERSAL);
        $traversal_edge = $this->getPointer(static::TRAVERSAL_EDGE);

        if ($strict || $return->checkUsed($traversal_edge)) {
            $statement->append('LET');
            $statement->append($traversal_edge);
            $statement->append('=');
            $statement->append($traversal . chr(46) . 'edges');
        }

        $traversal_vertex = $this->getPointer(static::TRAVERSAL_VERTEX);
        if ($strict || $return->checkUsed($traversal_vertex)) {
            $statement->append('LET');
            $statement->append($traversal_vertex);
            $statement->append('=');
            $statement->append($traversal . chr(46) . 'vertices');
        }

        if ($strict) $this->addStrict($parser, $statement);

        $return = $return->getStatement();
        $return_query = $return->getQuery();
        if (0 !== strlen($return_query)) {
            $query = $statement->addFromStatement($return);
            $statement->append($query);
            $this->shouldLimit($statement);
        } else {
            $this->shouldLimit($statement);
            $statement->append('RETURN DISTINCT');
            $statement->append(chr(123), false);
            $statement->append(Vertex::TYPE, false);
            $statement->append(chr(58));
            $statement->append($this->getPointer(static::VERTEX), false);
            $statement->append(chr(44));
            $statement->append(Edge::TYPE, false);
            $statement->append(chr(58));
            $statement->append($this->getPointer(static::EDGE), false);
            $statement->append(chr(125), false);
        }

        return $statement;
    }

    /**
     * If the first document has a _key field, or if the first document has a _id field and it is not
     * the default value, then we should search
     * 
     * @param Initiator initiator The initiator object.
     * 
     * @return The `shouldSearch` method returns a boolean value.
     */
    
    protected static function shouldSearch(Initiator $initiator) : bool
    {
        $start = $initiator->getStart();
        $first = $initiator->begin();

        $search = $first->getAllFieldsValues(true, false);
        $search_remove = [
            $first->getField(Arango::KEY)->getName(),
            $first->getField(Arango::ID)->getName()
        ];
        $search = array_diff_key($search, array_flip($search_remove));
        $search = count($start) !== 1 || $first->getField(Arango::ID)->isDefault() || count($search) > 0;
        return $search;
    }

    /**
     * If a limit is set, it will be added to the query
     * 
     * @param Statement statement The statement to be modified.
     * 
     * @return The query with the LIMIT clause.
     */
    
    protected function shouldLimit(Statement $statement) : Statement
    {
        $limit = $this->getLimit();
        $limit_value = $limit->get();
        if (null === $limit_value) return $statement;

        $notation = 'LIMIT' . chr(32);
        $notation_offset = $limit->getOffset();
        if (null !== $notation_offset) $notation .= $notation_offset . chr(44) . chr(32);
        $notation .= $limit_value;

        $query = $statement->getQuery();
        $query_return = strripos($query, 'return');
        if (false === $query_return) return $statement->append($notation);

        $query = trim(substr($query, 0, $query_return)) . chr(32) . $notation . chr(32) . trim(substr($query, $query_return));
        return $statement->overwrite($query);
    }

    /**
     * Set the limit for the query
     * 
     * @param Limit limit The limit to apply to the query.
     */
    
    protected function setLimit(Limit $limit) : void
    {
        $this->limit = $limit;
    }

    /**
     * Returns the value of the `with` property
     * 
     * @return The value of the `with` property.
     */
    
    protected function getWith() : bool
    {
        return $this->with;
    }

    /**
     * The function checks if the shortest path algorithm is being used, and if so, it checks if the
     * shortest path algorithm should be pruned. If so, it adds a prune statement to the query
     * 
     * @param Parser parser The parser object.
     * @param Statement statement The statement object that we're adding the filter to.
     */
    
    protected function setPrimaryConditions(Parser $parser, Statement $statement) : void
    {
        $traversal = $this->getPointer(static::TRAVERSAL);
        if (true === $this->shouldUseStrict($parser) && static::class === Shortestpath::class
            || static::class === Shortestpath::class && $this->getReturn()->checkUsed($traversal)) return;

        $edges = $parser->getTargetsEdge();
        $edges = array_map(function (Edge $edge) use ($statement) {
            $conditions = $this->primary($statement, $edge);
            $conditions_vertex = $edge->vertex();
            $conditions_vertex = $this->primary($statement, $conditions_vertex);
            $conditions_vertex = trim($conditions_vertex);
            if (strlen($conditions_vertex) > 0) $conditions .= chr(32) . 'AND' . chr(32) . $conditions_vertex;
            return $conditions;
        }, $edges);

        $edges = array_filter($edges);
        if (empty($edges)) return;

        $edges = array_unique($edges, SORT_STRING);
        $edges = implode(chr(32) . 'OR' . chr(32), $edges);

        if (method_exists($this, 'shouldUsePrune')
            && true === $this->shouldUsePrune($parser)) $statement->append('PRUNE' . chr(32) . $edges);

        $statement->append('FILTER');
        $statement->append($edges);
    }

    /**
     * Given a statement and an entity, return the conditions that must be met in order to find the
     * entity
     * 
     * @param Statement statement The statement to be executed.
     * @param Entity entity The entity to be deleted.
     * 
     * @return The conditions for the query.
     */
    
    protected function primary(Statement $statement, Entity $entity) : string
    {
        $pointer = Edge::TYPE === $entity->getType() ? static::EDGE : static::VERTEX;
        $pointer = $this->getPointer($pointer);

        $conditions = [];
        $collection = 'NOT_NULL' . chr(40) . $pointer . chr(41) . chr(32) . 'AND IS_SAME_COLLECTION' . chr(40) . chr(34) . $entity->getCollectionName() . chr(34) . chr(44) . chr(32) . $pointer . chr(41);
        array_push($conditions, $collection);

        if ($matches = $this->matches($pointer, $statement, new Document($entity))) {
            $matches = array_unique($matches, SORT_STRING);
            $matches = count($matches) > 1 ? chr(40) . implode(chr(32) . 'OR' . chr(32), $matches) . chr(41) : reset($matches);
            array_push($conditions, $matches);
        }

        $conditions_glue = chr(32) . 'AND' . chr(32);
        $conditions = implode($conditions_glue, $conditions);
        return $conditions;
    }
}