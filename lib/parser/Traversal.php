<?PHP

namespace ArangoDB\parser;

use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;

/* This class is used to store the edges of a graph */

class Traversal
{
    protected $edges = []; // (array) Edge

    /**
     * Clone the object and all its properties
     * 
     * @return The object is being returned.
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
     * Add an edge to the graph
     * 
     * @return The object itself.
     */
    
    public function pushEdges(Edge ...$edges) : self
    {
        array_push($this->edges, ...$edges);
        return $this;
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
