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

    public function __construct(Initiator $core, ...$arguments)
    {
        parent::__construct($core, ...$arguments);
        $this->setLimit(new Limit());
    }

    public function useWith(bool $with = true) : self
    {
        $this->with = $with;
        return $this;
    }

    public function getWithCollections() : array
    {
        return $this->with_collections;
    }

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

    public function pushWithCollection(string ...$with_collections) : self
    {
        array_push($this->with_collections, ...$with_collections);
        return $this;
    }

    public function getLimit() : Limit
    {
        return $this->limit;
    }

    public function run() :? array
    {
        return $this->getStatement()->execute();
    }

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

    protected function setLimit(Limit $limit) : void
    {
        $this->limit = $limit;
    }

    protected function getWith() : bool
    {
        return $this->with;
    }

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

    protected function primary(Statement $statement, Entity $entity) : string
    {
        $pointer = Edge::TYPE === $entity->getType() ? static::EDGE : static::VERTEX;
        $pointer = $this->getPointer($pointer);

        $conditions = [];
        $collection = 'NOT_NULL(' . $pointer . ') AND IS_SAME_COLLECTION("' . $entity->getCollectionName() . '",' . chr(32) . $pointer . ')';
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