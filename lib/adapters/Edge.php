<?PHP

namespace ArangoDB\adapters;

use Entity\Map as Entity;
use Entity\Adapter;

use ArangoDB\Initiator;

use ArangoDB\entity\Vertex;
use ArangoDB\entity\Edge as ADBEdge;
use ArangoDB\entity\common\Arango;
use ArangoDB\adapters\Vertex as CVertex;

class Edge extends Adapter
{
    protected $from; // Vertex
    protected $to;   // Vertex

    public function __clone()
    {
        $copy = clone $this->getTo();
        $copy->cloneHashEntity($this->getTo());
        $this->setTo(null, $copy);
    }

    public function __construct(ADBEdge $edge)
    {
        $this->target($edge);
    }

    public function setFrom(?ADBEdge $edge, Vertex $vertex) : void
    {
        $this->from = $vertex;
    }

    public function getFrom() : Vertex
    {
        return $this->from;
    }

    public function setTo(?ADBEdge $edge, Vertex $vertex) : void
    {
        $this->to = $vertex;
    }

    public function getTo() : Vertex
    {
        return $this->to;
    }

    public function vertex(ADBEdge $edge, Vertex $vertex = null, bool $interconnect = false) : Vertex
    {
        $to = $this->getTo();
        if (null === $vertex) return $to;

        $to->cloneAllFieldsFromEntity($vertex);
        if (true !== $interconnect) return $to;
        if ($vertex->getAdapter() instanceof CVertex)
            $to->setContainer($vertex->getContainer());
        return $to;
    }

    public function branch(ADBEdge $edge) : ADBEdge
    {
        $name = $edge->getReflection()->getShortName();
        $node = $this->getFrom()->useEdge($name);
        $node->cloneAllFieldsFromEntity($edge);

        $direction = $edge->getForceDirection();
        if (null !== $direction) $node->setForceDirection($direction);
        return $node;
    }

    protected function target(ADBEdge $edge) : void
    {
        $vertex_path = $edge->getTarget();
        $vertex_path = $vertex_path . '\\' . 'Vertex';
        $vertex = Entity::factory($vertex_path);

        Initiator::entityAttachAdapter($vertex, Initiator::ADAPTER_V_NAME);

        $this->setTo(null, $vertex);
    }
}