<?PHP

namespace ArangoDB;

use stdCalss;

use Knight\armor\CustomException;

use ArangoDB\entity\common\Arango;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\parser\Route;
use ArangoDB\parser\Traversal;
use ArangoDB\operations\common\base\Document;

class Parser
{
    protected $start;                 // Vertex
    protected $routes = [];           // (array)
    protected $edges = [];            // (array)
    protected $edges_targets = [];    // (array)
    protected $with_collections = []; // (array)

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

    public function getTargetsEdge() : array
    {
        return $this->edges_targets;
    }

    public function getTargetsVertex() : array
    {
        $response = $this->getTargetsEdge();
        $response = array_map(function (Edge $edge) {
            return $edge->vertex();
        }, $response);
        return $response;
    }

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

    public function getEdges() : array
    {
        return $this->edges;
    }

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

    public function getRoutes() : array
    {
        return $this->routes;
    }

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

    protected function getStart() : Vertex
    {
        return $this->start;
    }

    protected function setStart(Vertex $start) : void
    {
        $this->start = $start;
    }

    protected function pushRoutes(Route ...$routes) : void
    {
        array_push($this->routes, ...$routes);
    }

    protected function pushEdges(Edge ...$edges) : void
    {
        array_push($this->edges, ...$edges);
    }

    protected function pushEdgesTargets(Edge ...$edges_targets) : void
    {
        array_push($this->edges_targets, ...$edges_targets);
    }

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