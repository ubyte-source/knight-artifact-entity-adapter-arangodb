<?PHP

namespace ArangoDB\adapters;

use DirectoryIterator;

use Entity\Map as Entity;
use Entity\Adapter;

use ArangoDB\entity\Edge;
use ArangoDB\entity\Vertex as ADBVertex;
use ArangoDB\adapters\map\Container;

class Vertex extends Adapter
{
    protected $container; // Container

    public function __construct()
    {
        $this->setContainer(null, new Container());
    }

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

    public function setContainer(?ADBVertex $vertex, Container $container) : self
    {
        $this->container = $container;
        return $this;
    }

    public function getContainer() : Container
    {
        return $this->container;
    }

    public function useEdge(ADBVertex $vertex, string $edgename, Edge ...$edges) : Edge
    {
        $namespace = $vertex->getReflection()->getNamespaceName();
        $node_namespace = $namespace . '\\' . 'edges' . '\\' . $edgename;
        $node = $this->factory($vertex, $node_namespace);
        $node_vertex = $node->vertex();

        $last = array_pop($edges);
        if (null !== $last) {
            $last_vertex = $last->vertex();
            $node->cloneAllFieldsFromEntity($last);
            $node->vertex($last_vertex);
            $direction = $last->getForceDirection();
            if (null !== $direction) $node->setForceDirection($direction);
        }

        array_walk($edges, function (Edge $edge) use ($vertex, $node_vertex, $node_namespace) {
            $instance = $this->factory($vertex, $node_namespace);
            $instance->cloneAllFieldsFromEntity($edge);
            $instance_vertex = $edge->vertex();
            $instance_vertex = $instance->vertex($instance_vertex);

            $direction = $edge->getForceDirection();
            if (null !== $direction) $instance->setForceDirection($direction);
            if ($instance_vertex->getReflection()->getName() === $node_vertex->getReflection()->getName())
                $instance_vertex->setContainer(null, $node_vertex->getContainer());
        });

        return $node;
    }

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

    protected function factory(ADBVertex $vertex, string $parameter) : Edge
    {
        $node = Entity::factory($parameter);
        $this->getContainer()->pushEdges($vertex, $node);
        return $node;
    }
}