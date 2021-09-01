<?PHP

namespace ArangoDB\operations\common;

use ArangoDB\Initiator;
use ArangoDB\Statement;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\base\SReturn;
use ArangoDB\operations\common\interfaces\Base as BaseInterface;

abstract class Base implements BaseInterface
{
    const RANDOM_CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyz';

    protected $core;                // Initiator
    protected $skip_statement = []; // (array)
    protected $pointers = [];       // (array)
    protected $return;              // SReturn

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

    public function __construct(Initiator $core, ...$arguments)
    {
        $this->setCore($core);
        $this->setReturn(new SReturn());
    }

    public function getReturn() : SReturn
    {
        return $this->return;
    }

    public function pushStatementSkipValues(string ...$values) : self
    {
        array_push($this->skip_statement, ...$values);
        return $this;
    }

    public function getStatementSkipValues(string ...$values) : array
    {
        return $this->skip_statement;
    }

    public function setPointer(string $name, $value = null, Statement $statement = null) : string
    {
        if (null === $value) while (true) if (false === in_array($randm = $this->getRandomString(), $this->pointers)) return $this->pointers[$name] = $randm;
        if (null === $statement) return $this->pointers[$name] = $value;
        $bound = $statement->bound($value);
        $bound = reset($bound);
        return $this->pointers[$name] = $bound;
    }

    public function getPointer(string $name) : string
    {
        return array_key_exists($name, $this->pointers) ? $this->pointers[$name] : $this->setPointer($name);
    }

    protected function setReturn(SReturn $return) : void
    {
        $this->return = $return;
    }

    final protected function setCore(Initiator $core) : void
    {
        $this->core = $core;
    }

    final protected function getCore() : Initiator
    {
        return $this->core;
    }

    private function getRandomString(int $length = 7) : string
    {
		$string = chr(69);
		for ($i = 0, $length = strlen(static::RANDOM_CHARACTERS) - 1; $i < $length; $i++) {
			$character_random_position = rand(0, $length);
			$string .= static::RANDOM_CHARACTERS[$character_random_position];
		}
		return $string;
    }
}