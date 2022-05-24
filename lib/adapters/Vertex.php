<?PHP

namespace ArangoDB\adapters;

use DirectoryIterator;

use Entity\Map as Entity;
use Entity\Adapter;

use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex as ADBVertex;
use ArangoDB\adapters\map\Container;
use ArangoDB\adapters\Edge as CEdge;

/* The Vertex class is a class that extends the Adapter class. It is used to create edges */

class Vertex extends Adapter
{
    protected $container; // Container

    /**
     * The constructor sets the container to null and creates a new Container object
     */
    
    public function __construct()
    {
        $this->setContainer(null, new Container());
    }

    /**
     * Clone the object and all of its properties
     * 
     * @return Nothing.
     */
    
    public function __clone()
    {
        $variables = get_object_vars($this);
        $variables = array_keys($variables);
        $variables_glue = [];
        foreach ($variables as $name) array_push($variables_glue, array(&$this->$name));
        array_walk_recursive($variables_glue, function (&$item, $name) {
            if (false === is_object($item)) return;
            $item = clone $item;
        });
    }

    /**
     * * Sets the container for the vertex
     * 
     * @param vertex The vertex that is being added to the container.
     * @param Container container The container that the vertex is in.
     * 
     * @return The object itself.
     */
    
    public function setContainer(?ADBVertex $vertex, Container $container) : self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Returns the container that was used to create this object
     * 
     * @return The Container object.
     */
    
    public function getContainer() : Container
    {
        return $this->container;
    }
    
     /**
      * *This function is used to create a new edge instance from an existing edge instance.*
      * 
      * The function takes three parameters:
      * 
      * * `$vertex` - The vertex instance that the edge will be attached to.
      * * ` $edgename` - The name of the edge class to be instantiated.
      * * `...$edges` - An array of edge instances that will be cloned and attached to the new edge
      * instance
      * 
      * @param ADBVertex vertex The vertex that the edge is being added to.
      * @param string edgename The name of the edge.
      * 
      * @return The last edge in the chain.
      */
     
    public function useEdge(ADBVertex $vertex, string $edgename, Edge ...$edges) : Edge
    {
        $namespace = $vertex->getReflection()->getNamespaceName();
        $node_namespace = $namespace . '\\' . 'edges' . '\\' . $edgename;
        $node = $this->factory($vertex, $node_namespace);
        $node_vertex = $node->vertex();

        $last = array_pop($edges);
        if (null !== $last) {
            $node->cloneAllFieldsFromEntity($last);
            if ($last->getAdapter() instanceof CEdge)
                $node->vertex($last->vertex());

            $direction = $last->getForceDirection();
            if (null !== $direction) $node->setForceDirection($direction);
        }

        array_walk($edges, function (Edge $edge) use ($vertex, $node_vertex, $node_namespace) {
            $instance = $this->factory($vertex, $node_namespace);
            $instance->cloneAllFieldsFromEntity($edge);

            $direction = $edge->getForceDirection();
            if (null !== $direction) $instance->setForceDirection($direction);
            if ($edge->getAdapter() instanceof CEdge) $instance->vertex($edge->vertex());
        });

        return $node;
    }

    /**
     * Get all the edge names that are usable by the given vertex
     * 
     * @param ADBVertex vertex The vertex to get all usable edges from.
     * @param bool shortname If true, the collection name will be the short name.
     * 
     * @return An array of edge names.
     */
    
    public function getAllUsableEdgesName(ADBVertex $vertex, bool $shortname = true) : array
    {
        $reflection = $vertex->getReflection();
        $path = $reflection->getFileName();
        $path = dirname($path) . DIRECTORY_SEPARATOR . 'edges';
        if (!file_exists($path)
            || !is_dir($path)) return [];

        $directory = new DirectoryIterator($path);
        $namespace = $reflection->getNamespaceName();
        $result_collections = [];
        foreach ($directory as $info) {
            if ($info->isDot()
                || $info->isDir()) continue;

            $collection_filename_extension_length = $info->getExtension();
            $collection_filename_extension_length = strlen($collection_filename_extension_length);
            $collection_filename_extension_length *= -1;
            $collection_filename_extension_length += -1;
            $collection_filename = $info->getFilename();
            $collection = substr($collection_filename, 0, $collection_filename_extension_length);
            $collection = $namespace . '\\' . 'edges' . '\\' . $collection;
            $collection = new $collection();
            if (true === $shortname) $collection = $collection->getReflection()->getShortName();

            array_push($result_collections, $collection);
        }
        return $result_collections;
    }

    /**
     * Create a new edge from the current vertex to the new vertex
     * 
     * @param ADBVertex vertex The vertex that is being connected to the edge.
     * @param string parameter The name of the parameter to use for the edge.
     * 
     * @return The edge.
     */
    
    protected function factory(ADBVertex $vertex, string $parameter) : Edge
    {
        $node = Entity::factory($parameter);
        $this->getContainer()->pushEdges($vertex, $node);
        return $node;
    }
}
