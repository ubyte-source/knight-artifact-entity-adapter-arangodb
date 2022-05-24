<?PHP

namespace ArangoDB\adapters;

use Entity\Map as Entity;
use Entity\Adapter;

use ArangoDB\Initiator;

use ArangoDB\entity\Vertex;
use ArangoDB\entity\Edge as ADBEdge;
use ArangoDB\entity\common\Arango;
use ArangoDB\adapters\Vertex as CVertex;

/* This class is used to create a new edge between two vertices */

class Edge extends Adapter
{
    protected $from; // Vertex
    protected $to;   // Vertex

    /**
     * Clone the object and set the new object's `To` property to the same value as the original
     * object's `To` property
     */
    
    public function __clone()
    {
        $copy = clone $this->getTo();
        $copy->cloneHashEntity($this->getTo());
        $this->setTo(null, $copy);
    }

    /**
     * The constructor takes an ADBEdge object as a parameter and stores it in the target property
     * 
     * @param ADBEdge edge The edge that is being created.
     */
    
    public function __construct(ADBEdge $edge)
    {
        $this->target($edge);
    }

    /**
     * * Sets the `from` vertex of the edge
     * 
     * @param edge The edge that is being set as the from edge.
     * @param Vertex vertex The vertex that is the end of the edge.
     */
    
    public function setFrom(?ADBEdge $edge, Vertex $vertex) : void
    {
        $this->from = $vertex;
    }

    /**
     * Get the vertex that this edge is coming from
     * 
     * @return The from vertex.
     */
    
    public function getFrom() : Vertex
    {
        return $this->from;
    }

    /**
     * Set the edge's destination vertex
     * 
     * @param edge The edge that is being set to a new value.
     * @param Vertex vertex The vertex that is the end of the edge.
     */
    
    public function setTo(?ADBEdge $edge, Vertex $vertex) : void
    {
        $this->to = $vertex;
    }

    /**
     * Returns the vertex that this edge is pointing to
     * 
     * @return The to property of the Edge object.
     */
    
    public function getTo() : Vertex
    {
        return $this->to;
    }

    /**
     * * Given an edge and a vertex, return the vertex at the other end of the edge
     * 
     * @param ADBEdge edge The edge that is being traversed.
     * @param Vertex vertex The vertex to connect to.
     * @param bool interconnect If true, the vertex will be connected to the previous vertex.
     * 
     * @return A new instance of the `Vertex` class.
     */
    
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

    /**
     * Create a new node that is a copy of the current node, but with a different edge
     * 
     * @param ADBEdge edge The edge to branch from.
     */
    
    public function branch(ADBEdge $edge) : ADBEdge
    {
        $name = $edge->getReflection()->getShortName();
        $node = $this->getFrom()->useEdge($name);
        $node->cloneAllFieldsFromEntity($edge);

        $direction = $edge->getForceDirection();
        if (null !== $direction) $node->setForceDirection($direction);
        return $node;
    }

    /**
     * * The function takes an edge as a parameter. 
     * * It then creates a vertex object from the target of the edge. 
     * * It then attaches the vertex to the initiator. 
     * * It then sets the initiator's to property to the vertex. 
     * 
     * The first thing the function does is create a variable. 
     * This variable is set to the target of the edge. 
     * 
     * The target of an edge is the name of the class that the edge is pointing to. 
     * 
     * The  variable is then concatenated with the string 'Vertex'. 
     * This is done so that the variable can be used to create a new vertex object. 
     * 
     * The variable is then passed to the Entity::factory() function. 
     * 
     * @param ADBEdge edge The edge that is being traversed.
     */
    
    protected function target(ADBEdge $edge) : void
    {
        $vertex_path = $edge->getTarget();
        $vertex_path = $vertex_path . '\\' . 'Vertex';
        $vertex = Entity::factory($vertex_path);

        Initiator::entityAttachAdapter($vertex, Initiator::ADAPTER_V_NAME);

        $this->setTo(null, $vertex);
    }
}
