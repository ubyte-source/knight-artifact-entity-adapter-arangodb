<?PHP

namespace ArangoDB;

use ReflectionClass;

use Knight\armor\CustomException;

use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Base;

/* The Initiator is a class that is used to start a query */

final class Initiator
{
    const ADAPTER_V_NAME = 'Vertex';
    const ADAPTER_E_NAME = 'Edge';

    protected $start = [];     // (array) Vertex
    protected $adapter = true; // (bool)

    /**
     * This is the constructor function
     */

    protected function __construct() {}

    /**
     * Get the namespace name of the class
     * 
     * @return The namespace name of the class.
     */
    
    public static function getNamespaceName() : string
    {
        $class = new ReflectionClass(static::class);
        return $class->getNamespaceName();
    }

    /**
     * Attach an adapter to an entity
     * 
     * @param Arango entity The entity to attach the adapter to.
     * @param string adapter_name The name of the adapter to use.
     */
    
    public static function entityAttachAdapter(Arango $entity, string $adapter_name) : void
    {
        $namespace = static::getNamespaceName();
        $entity->useAdapter($adapter_name, $namespace);
    }

    /**
     * Clone the object and all its properties
     * 
     * @return The object is being cloned and the object is being returned.
     */
    
    public function __clone()
    {
        $variables = get_object_vars($this);
        $variables = array_keys($variables);
        $variables_glue = [];
        foreach ($variables as $name) array_push($variables_glue, array(&$this->$name));
        array_walk_recursive($variables_glue, function (&$item, $name) {
            if (false === is_object($item)) return;
            $item_clone = clone $item;
            if ($item_clone instanceof Arango) $item_clone->cloneHashEntity($item);
            $item = $item_clone;
        });
    }

    /**
     * If the method name is not a valid operation, throw an exception
     * 
     * @param string method The name of the method that was called.
     * @param array arguments The arguments passed to the method.
     * 
     * @return The `__call` method returns an instance of the `Base` class.
     */
    
    public function __call(string $method, array $arguments) : Base
    {
        $name = strtolower($method);
        $name = ucfirst($name);

        $instance = __namespace__ . '\\' . 'operations' . '\\' . $name;
        $instance = new $instance($this, $arguments);
        if ($instance instanceof Base) return $instance;

        throw new CustomException('developer/arangodb/initiator/operation');
    }

    /**
     * Creates a new instance of the class and adds the given vertices to the start array
     * 
     * @return The instance of the class.
     */
    
    public static function start(Vertex ...$vertices) : self
    {
        $instance = new static();
        array_push($instance->start, $vertex = array_pop($vertices));
        $instance->adapterManager($vertex);

        if (!!$vertices) $instance->push(...$vertices);

        return $instance;
    }

    /**
     * If the adapter is set to true, then the adapter manager is called on each vertex
     * 
     * @param bool adapter If true, the adapter will be used.
     * 
     * @return The object itself.
     */
    
    public function setUseAdapter(bool $adapter = true) : self
    {
        $this->adapter = $adapter;
        $vertices = $this->getStart();
        foreach ($vertices as $vertex) $this->adapterManager($vertex);
        return $this;
    }

    /**
     * Returns the value of the `adapter` property
     * 
     * @return The value of the `adapter` property.
     */
    
    public function getUseAdapter() : bool
    {
        return $this->adapter;
    }

    /**
     * * Push a vertex to the start of the list
     * 
     * @return Nothing.
     */
    
    protected function push(Vertex ...$vertices) : void
    {
        $first = $this->begin();
        if (null === $first) return;

        $name = $first->getReflection()->getName();
        foreach ($vertices as $vertex) {
            if ($name !== $vertex->getReflection()->getName()) throw new CustomException('developer/arangodb/vertices/equal');
            $this->adapterManager($vertex);
            if ($vertex->hasAdapter()) $vertex->setContainer($first->getContainer());
        }

        array_push($this->start, ...$vertices);
    }

    /**
     * Returns the start of the current iteration
     * 
     * @return An array of integers.
     */
    
    public function getStart() : array
    {
        return $this->start;
    }

    /**
     * Return the first vertex in the graph
     * 
     * @return The first vertex in the start list.
     */
    
    public function begin() :? Vertex
    {
        $start = $this->getStart();
        return reset($start) ?: null;
    }

    /**
     * Reset the start array
     * 
     * @return The object itself.
     */
    
    public function reset() : self
    {
        $this->start = [];
        return $this;
    }

    /**
     * If the adapter is not being used, remove it from the vertex. If the adapter is being used, add
     * it to the vertex
     * 
     * @param Vertex vertex The vertex to attach the adapter to.
     */
    
    protected function adapterManager(Vertex $vertex) : void
    {
        if (false === $this->getUseAdapter() && $vertex->hasAdapter()) {
            $vertex->unsetAdapter();
        } elseif ($this->getUseAdapter() && $vertex->hasAdapter() === false) {
            static::entityAttachAdapter($vertex, static::ADAPTER_V_NAME);
        }
    }
}