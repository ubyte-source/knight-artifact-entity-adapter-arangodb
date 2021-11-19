<?PHP

namespace ArangoDB\adapters\map;

use Knight\armor\CustomException;

use ArangoDB\Initiator;
use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;

final class Container
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

    public function removeEdgesByName(string ...$names) : self
    {
        $this->edges = array_filter($this->edges, function (Edge $edge) use ($names) {
            return false === in_array($edge->getReflection()->getShortName(), $names);
        });
        return $this;
    }

    public function getEdgesByName(string ...$names) : array
    {
        $edges = $this->getEdges();
        $edges = array_filter($edges, function (Edge $edge) use ($names) {
            return in_array($edge->getReflection()->getShortName(), $names);
        });
        return $edges;
    }

    public function getEdges() : array
    {
        return $this->edges;
    }
}