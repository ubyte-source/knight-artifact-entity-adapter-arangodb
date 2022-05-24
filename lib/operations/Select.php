<?PHP

namespace ArangoDB\operations;

use ArangoDB\Parser;
use ArangoDB\Statement;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\common\Choose;
use ArangoDB\operations\features\Prune;
use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;

/* This class is used to create a traversal query */

class Select extends Choose
{
    use Prune;

    protected $max_hop; // (int)

    /**
     * If the query is a simple query, then it will return the document. If the query is a complex
     * query, then it will return the document and the sub-query
     * 
     * @param Parser parser The parser object.
     */
    
    protected function overwrite(Parser $parser, Vertex ...$start_vertices) : Statement
    {
        $statement = new Statement();
        $start_vertices_first = reset($start_vertices);
        $start_vertices_first_data = new Document($start_vertices_first);
        $start_vertices_first_data = $start_vertices_first_data->getValues();
        $start_vertices_first_data = (object)$start_vertices_first_data;

        if (!!$skip_values = $this->getStatementSkipValues()) $statement->pushSkipValues(...$skip_values);

        if (count($start_vertices) > 1 || count((array)$start_vertices_first_data) !== 1 || !property_exists($start_vertices_first_data, Arango::ID) || strpos($start_vertices_first_data->_id, '%') !== false) {
            $smooth_with_collections_use = $this->getWith();
            if (true === $smooth_with_collections_use) {
                $parser_with_collections = $parser->getWithCollections();
                $smooth_with_collections = $this->getWithCollections();
                $smooth_with_collections = array_merge($parser_with_collections, $smooth_with_collections);
                $smooth_with_collections = array_unique($smooth_with_collections, SORT_STRING);
                $smooth_with_collections = implode(chr(44) . chr(32), $smooth_with_collections);
                $statement->append('WITH ' . $smooth_with_collections);
            }
            $start_vertices_first_collection_name = $start_vertices_first->getCollectionName();
            $statement_vertex = $this->getPointer(Choose::VERTEX);
            $statement->append('FOR');
            $statement->append($statement_vertex);
            $statement->append('IN');
            $statement->append($start_vertices_first_collection_name);

            $start_vertices_document = array_map(function (Vertex $vertex) {
                $vertex_data = new Document($vertex);
                return $vertex_data;
            }, $start_vertices);
            if ($start_vertices_document_matches = $this->matches($statement_vertex, $statement, ...$start_vertices_document)) {
                $start_vertices_document_matches_conditions = implode(' AND ', $start_vertices_document_matches);
                $statement->append('FILTER');
                $statement->append($start_vertices_document_matches_conditions);
            }

            $statement_return = $this->getReturn()->getStatement();
            $statement_return_query = $statement_return->getQuery();
            if (0 === strlen($statement_return_query)) return $statement->append('RETURN {vertex: ' . $statement_vertex . '}');

            $query = $statement->addFromStatement($statement_return);
            $statement->append($query);
            $this->shouldLimit($statement);

            return $statement;
        }

        $bound = $statement->bound($start_vertices_first_data->_id);
        $bound = reset($bound);
        $named = $this->getPointer(Choose::VERTEX);
        $statement->append('LET');
        $statement->append($named);
        $statement->append('=');
        $statement->append('DOCUMENT' . chr(40) . $bound . chr(41));
        $statement->append('FILTER null !=');
        $statement->append($named);

        $statement_return = $this->getReturn()->getStatement();
        $statement_return_query = $statement_return->getQuery();
        if (0 === strlen($statement_return_query)) return $statement->append('RETURN' . chr(32) . $named);
        $query = $statement->addFromStatement($statement_return);
        $statement->append($query);
        return $statement;
    }

    /**
     * * For each vertex, edge, and traversal in the range of 1 to the maximum hop, 
     *   choose a start vertex
     * 
     * @param Parser parser The parser object.
     * @param Statement statement The statement to which the FOR loop is appended.
     */
    
    protected function main(Parser $parser, Statement $statement) : void
    {
        $max = $this->getMaxHop() ?? '1e3';
        $statement_vertex = $this->getPointer(Choose::VERTEX);
        $statement_edge = $this->getPointer(Choose::EDGE);
        $statement_traversal = $this->getPointer(Choose::TRAVERSAL);

        $statement->append('FOR');
        $statement->append($statement_vertex . chr(44));
        $statement->append($statement_edge . chr(44));
        $statement->append($statement_traversal);
        $statement->append('IN');
        $statement->append('1..' . $max);
        $statement->append('ANY');
        $statement_variable_start = $this->getPointer('start');
        $statement->append($statement_variable_start);
    }

    /**
     * * Set the maximum number of hops for the packet
     * 
     * @param int hop The number of hops to take.
     * 
     * @return The object itself.
     */
    
    public function setMaxHop(int $hop) : self
    {
        $this->max_hop = $hop;
        return $this;
    }

    /**
     * "Get the maximum number of hops."
     * 
     * The function name is getMaxHop
     * 
     * @return The max_hop property of the object.
     */
    
    public function getMaxHop() :? int
    {
        return $this->max_hop;
    }
}
