<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\Parser;
use ArangoDB\entity\Vertex;

trait Prune
{
    protected $prune = true; // (bool)

    public function usePrune(bool $prune = true) : self
    {
        $this->prune = $prune;
        return $this;
    }

    protected function getPrune() : bool
    {
        return $this->prune;
    }

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