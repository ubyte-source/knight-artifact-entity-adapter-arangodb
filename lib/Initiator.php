<?PHP

namespace ArangoDB;

use ReflectionClass;

use Knight\armor\CustomException;

use ArangoDB\entity\Vertex;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Base;

final class Initiator
{
    const ADAPTER_V_NAME = 'Vertex';
    const ADAPTER_E_NAME = 'Edge';

    protected $start = [];     // (array) Vertex
    protected $adapter = true; // (bool)

    protected function __construct() {}

    public static function getNamespaceName() : string
    {
        $class = new ReflectionClass(static::class);
        return $class->getNamespaceName();
    }

    public static function entityAttachAdapter(Arango $entity, string $adapter_name) : void
    {
        $namespace = static::getNamespaceName();
        $entity->useAdapter($adapter_name, $namespace);
    }

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

    public function __call(string $method, array $arguments) : Base
    {
        $name = strtolower($method);
        $name = ucfirst($name);

        $instance = __namespace__ . '\\' . 'operations' . '\\' . $name;
        $instance = new $instance($this, $arguments);
        if ($instance instanceof Base) return $instance;

        throw new CustomException('developer/arangodb/initiator/operation');
    }

    public static function start(Vertex ...$vertices) : self
    {
        $instance = new static();
        array_push($instance->start, $vertex = array_pop($vertices));
        $instance->adapterManager($vertex);

        if (!!$vertices) $instance->push(...$vertices);

        return $instance;
    }

    public function setUseAdapter(bool $adapter = true) : self
    {
        $this->adapter = $adapter;
        $vertices = $this->getStart();
        foreach ($vertices as $vertex) $this->adapterManager($vertex);
        return $this;
    }

    public function getUseAdapter() : bool
    {
        return $this->adapter;
    }

    protected function push(Vertex ...$vertices) : int
    {
        $first = $this->begin();
        if (null === $first) return 1;

        $name = $first->getReflection()->getName();
        foreach ($vertices as $vertex) {
            if ($name !== $vertex->getReflection()->getName()) throw new CustomException('developer/arangodb/vertices/equal');
            $this->adapterManager($vertex);
            if ($vertex->hasAdapter()) $vertex->setContainer($first->getContainer());
        }

        return array_push($this->start, ...$vertices);
    }

    public function getStart() : array
    {
        return $this->start;
    }

    public function begin() :? Vertex
    {
        $start = $this->getStart();
        return reset($start) ?: null;
    }

    public function reset() : self
    {
        $this->start = [];
        return $this;
    }

    protected function adapterManager(Vertex $vertex) : void
    {
        if (false === $this->getUseAdapter() && $vertex->hasAdapter()) {
            $vertex->unsetAdapter();
        } elseif ($this->getUseAdapter() && $vertex->hasAdapter() === false) {
            static::entityAttachAdapter($vertex, static::ADAPTER_V_NAME);
        }
    }
}