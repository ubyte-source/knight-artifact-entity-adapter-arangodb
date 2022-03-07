<?PHP

namespace ArangoDB\adapters\map;

use Knight\armor\CustomException;

use ArangoDB\Initiator;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;

/* The Container class is used to store edges */

final class Container
{
    protected $edges = []; // (array) Edge

    /**
     * Clone the object and all of its properties
     * 
     * @return The cloned object.
     */
    
    public function __clone()
    {
        $variables = get_object_vars($this);
        $variables = array_keys($variables);
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
     * * The function accepts a vertex and an arbitrary number of edges. 
     * * It then checks to make sure the namespace of the vertex and the edge are the same. 
     * * It then attaches the adapter to the edge and sets the vertex as the from vertex. 
     * * Finally, it pushes the edge onto the edges array. 
     * 
     * Now, let's take a look at the code for the `pushVertices` function.
     * 
     * @param Vertex vertex The vertex to attach the edges to.
     * 
     * @return The container itself.
     */
    
    public function pushEdges(Vertex $vertex, Edge ...$edges) : self
    {
        $namespace = $vertex->getReflection()->getNamespaceName();
        $namespace = $namespace . '\\' . 'edges';
        array_walk($edges, function (Edge $edge) use ($namespace, $vertex) {
            $reflected = $edge->getReflection()->getNamespaceName();
            if ($namespace !== $reflected) throw new CustomException('developer/edge/container/namespace');
            if (false === $edge->hasAdapter()) Initiator::entityAttachAdapter($edge, Initiator::ADAPTER_E_NAME);
            $edge->setFrom($vertex);
        });

        array_push($this->edges, ...$edges);

        return $this;
    }

    /**
     * Remove all edges whose reflection's short name is in the given array of names
     * 
     * @return The object itself.
     */
    
    public function removeEdgesByName(string ...$names) : self
    {
        $this->edges = array_filter($this->edges, function (Edge $edge) use ($names) {
            return false === in_array($edge->getReflection()->getShortName(), $names);
        });
        return $this;
    }

    /**
     * Get all the edges that have a reflection with a short name that is in the given list of names
     * 
     * @return An array of edges.
     */
    
    public function getEdgesByName(string ...$names) : array
    {
        $edges = $this->getEdges();
        $edges = array_filter($edges, function (Edge $edge) use ($names) {
            return in_array($edge->getReflection()->getShortName(), $names);
        });
        return $edges;
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
}