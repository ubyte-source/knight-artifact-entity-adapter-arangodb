<?PHP

namespace ArangoDB;

use stdCalss;

use Knight\armor\CustomException;
/* This class is used to traverse the graph */

use ArangoDB\entity\common\Arango;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\parser\Route;
use ArangoDB\parser\Traversal;
use ArangoDB\operations\common\base\Document;

/* The Parser class is a class that is used to parse the routes of the graph */

class Parser
{
    protected $start;                 // Vertex
    protected $routes = [];           // (array)
    protected $edges = [];            // (array)
    protected $edges_targets = [];    // (array)
    protected $with_collections = []; // (array)

    /**
     * Clone the object and all of its properties
     * 
     * @return The object is being returned.
     */
    
    public function __clone()
    {
        $variables = get_object_vars($this);
        $variables = array_keys($variables);
        $variables_remove = [];
        array_push($variables_remove, 'start');
        $variables = array_diff($variables, $variables_remove);
        $variables_glue = [];
        foreach ($variables as $name) array_push($variables_glue, array(&$this->$name));
        array_walk_recursive($variables_glue, function (&$item, $name) {
            if (false === is_object($item)) return;
            $clone = clone $item;
            if ($clone instanceof Arango) $clone->cloneHashEntity($item);
            $item = $clone;
        });
    }

    /**
     * * The constructor takes a list of vertices and creates a route object.
     * * The route object is created by traversing the edges of the first vertex.
     * * The traversal is done by creating a list of edges and traversing them. 
     * @return The `Route` object.
     */
    
    public function __construct(Vertex ...$vertices)
    {
        $vertex = reset($vertices);
        if (false === $vertex) throw new CustomException('developer/arangodb/start/one');

        $this->setStart($vertex);
        $edges = $this->getStart()->hasAdapter() ? $this->getStart()->getContainer()->getEdges() : array();
        if (false === is_array($edges)) throw new CustomException('developer/arangodb/start/empty');

        if (empty($edges)) {
            $route = new Route();
            $this->pushRoutes($route);
            $route->pushDocuments(new Document($vertex));
        } else {
            $traversals = $this->traversals(...$edges);
            foreach ($traversals as $traversal) {
                $rim = $traversal->getEdges();
                $rim_clone = array_map(function (Edge $edge) {
                    $clone = clone $edge;
                    $clone->cloneHashEntity($edge);
                    return $clone;
                }, $rim);

                $ways = [];
                $edge_target = null;

                $route = new Route();
                $this->pushEdges(...$rim_clone);
                $this->pushRoutes($route);

                foreach ($rim_clone as $edge) {
                    $edge->setSafeMode(false);
                    $edge->setFrom($edge_target ?? $this->getStart());
                    $edge_target = $edge->vertex();

                    $container = [
                        $edge->getFrom(), // Vertex from
                        $edge,
                        $edge_target      // Vertex to
                    ];

                    $direction = $edge->getForceDirection() ?? $edge->getDirection();
                    $direction = $direction === Edge::INBOUND ? 2 : 0;

                    if (1 === count($vertices)) for ($i = 0; $i <= 2; $i = $i + 2) {
                        $id = $container[$i]->getField(Arango::ID)->getValue();
                        if (is_string($id) && strlen($id)) {
                            $name = $i === $direction ? Edge::FROM : Edge::TO;
                            $edge->getField($name)->setValue($id);
                        }
                    }

                    foreach ($container as $i => $entity) {
                        $handler = new Document($entity);
                        if ($i === 1) array_push($ways, $edge->getCollectionName());
                        $handler->setTraversal(...$ways);
                        $route->pushDocuments($handler);
                    }
                }

                $last_rim_clone = end($rim_clone);
                $this->pushEdgesTargets($last_rim_clone);
            }
        }
    }

    /**
     * Returns an array of the target edges
     * 
     * @return An array of edges.
     */
    
    public function getTargetsEdge() : array
    {
        return $this->edges_targets;
    }

    /**
     * Get all the targets of the edges in the graph
     * 
     * @return An array of Vertex objects.
     */
    
    public function getTargetsVertex() : array
    {
        $response = $this->getTargetsEdge();
        $response = array_map(function (Edge $edge) {
            return $edge->vertex();
        }, $response);
        return $response;
    }

    /**
     * Get the names of all the vertices that are targets of the edges in the graph
     * 
     * @return The names of the vertices that are targets of the edges in the graph.
     */
    
    public function getTargetsVertexName() : array
    {
        $response = $this->getTargetsVertex();
        $response = array_map(function (Vertex $vertex) {
            return $vertex->getCollectionName();
        }, $response);
        $response = array_unique($response, SORT_STRING);
        $response = array_values($response);
        return $response;
    }

    /**
     * Return an array of all the edges in the graph
     * 
     * @return An array of Edge objects.
     */
    
    public function getEdges() : array
    {
        return $this->edges;
    }

    /**
     * Returns an array of the names of all the edges in the graph
     * 
     * @return An array of strings.
     */
    
    public function getEdgesName() : array
    {
        $response = $this->getEdges();
        $response = array_map(function (Edge $edge) {
            return $edge->getCollectionName();
        }, $response);
        $response = array_unique($response, SORT_STRING);
        $response = array_values($response);
        return $response;
    }

    /**
     * Get the name of the edges with the direction
     * 
     * @return An array of edge names with directions.
     */
    
    public function getEdgesNameWithDirection() : array
    {
        $edges = $this->getEdges();
        $response = [];
        foreach ($edges as $edge) {
            $name = $edge->getCollectionName();
            $direction = $edge->getForceDirection() ?? $edge->getDirection();
            $direction = mb_strtoupper($direction);
            $direction = $direction . chr(32) . $name;
            $response[$name] = !array_key_exists($name, $response)
                || $response[$name] === $direction
                ? $direction
                : Edge::ANY . chr(32) . $name;
        }
        return array_values($response);
    }

    /**
     * Returns an array of all the routes that have been added to the router
     * 
     * @return An array of Route objects.
     */
    
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Get the collection names of the vertex and all the collections that are connected to it
     * 
     * @return The collection names of the start vertex, the end vertices of each edge, and the start
     * vertex of each edge.
     */
    
    public function getWithCollections() : array
    {
        $response = [];
        array_push($response, $this->getStart()->getCollectionName());

        $edges = $this->getEdges();
        foreach ($edges as $edge) array_push($response, $edge->vertex()->getCollectionName(), $edge->getFrom()->getCollectionName());

        $response = array_unique($response, SORT_STRING);
        $response = array_values($response);

        return $response;
    }

    /**
     * Return the start vertex
     * 
     * @return The start vertex.
     */
    
    protected function getStart() : Vertex
    {
        return $this->start;
    }

    /**
     * Set the start vertex
     * 
     * @param Vertex start The starting vertex.
     */
    
    protected function setStart(Vertex $start) : void
    {
        $this->start = $start;
    }

    /**
     * *This function pushes routes into the routes array.*
     */
    
    protected function pushRoutes(Route ...$routes) : void
    {
        array_push($this->routes, ...$routes);
    }

    /**
     * *This function pushes an array of edges to the edges array.*
     * 
     * The next function is a bit more complex. It's called `getEdges` and it returns an array of edges
     */
    
    protected function pushEdges(Edge ...$edges) : void
    {
        array_push($this->edges, ...$edges);
    }

    /**
     * *This function pushes the given edges to the edges_targets array.*
     */
    
    protected function pushEdgesTargets(Edge ...$edges_targets) : void
    {
        array_push($this->edges_targets, ...$edges_targets);
    }

    /**
     * Given a set of edges, return a set of traversals
     * 
     * @return An array of Traversals.
     */
    
    private function traversals(Edge ...$edges) : array
    {
        $response = [];
        foreach ($edges as $edge) {
            $rim = $edge->vertex()->getContainer()->getEdges();
            $traversals = $this->traversals(...$rim);
            if (empty($traversals)) {
                $traversal = new Traversal();
                $traversal->pushEdges($edge);
                array_push($response, $traversal);
            } else foreach ($traversals as $iterate) {
                $traversal = new Traversal();
                array_push($response, $traversal);
                $iterate_traversals = $iterate->getEdges();
                $traversal->pushEdges($edge, ...$iterate_traversals);
            }
        }
        return $response;
    }
}