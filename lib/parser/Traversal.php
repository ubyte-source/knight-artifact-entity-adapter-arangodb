<?PHP

namespace ArangoDB\parser;

use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;

class Traversal
{
    protected $edges = []; // (array) Edge

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

    public function pushEdges(Edge ...$edges) : int
    {
        return array_push($this->edges, ...$edges);
    }

    public function getEdges() : array
    {
        return $this->edges;
    }
}