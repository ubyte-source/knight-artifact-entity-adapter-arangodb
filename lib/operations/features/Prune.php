<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\Parser;
use ArangoDB\entity\Vertex;

/* The `Prune` trait is used to determine whether to use pruning. */

trait Prune
{
    protected $prune = true; // (bool)

    /**
     * If the prune parameter is true, then the prune method will be called
     * 
     * @param bool prune If true, the prune method will be called on the object.
     * 
     * @return Nothing.
     */
    
    public function usePrune(bool $prune = true) : self
    {
        $this->prune = $prune;
        return $this;
    }

    /**
     * Returns the value of the prune property
     * 
     * @return The value of the prune property.
     */
    
    protected function getPrune() : bool
    {
        return $this->prune;
    }

    /**
     * If the parser has more than one target vertex, or if the parser has more than one route, then we
     * can't prune
     * 
     * @param Parser parser The Parser object that is currently being used to parse the query.
     * 
     * @return The `shouldUsePrune()` method returns `true` if the `` parameter is `true` and the
     * parser has more than one target vertex.
     */
    
    protected function shouldUsePrune(Parser $parser) : bool
    {
        $prune = $this->getPrune();
        if (false === $prune) return false;

        $targets = $parser->getTargetsVertex();
        $targets = array_map(function (Vertex $vertex) {
            return $vertex->getCollectionName();
        }, $targets);
        $targets = array_unique($targets, SORT_STRING);
        if (1 < count($targets)) return false;

        $parser_routes = $parser->getRoutes();
        foreach ($parser_routes as $route) {
            $route_documents = $route->getDocuments();
            $route_documents_collections = [];
            foreach ($route_documents as $i => $document) if ($i > 0) {
                if (Vertex::TYPE !== $document->getEntity()->getType()) continue;
                array_push($route_documents_collections, $document->getEntity()->getCollectionName());
            }
            $route_documents_collections_last = array_pop($route_documents_collections);
            if (in_array($route_documents_collections_last, $route_documents_collections)) return false;
        }
        return true;
    }
}
