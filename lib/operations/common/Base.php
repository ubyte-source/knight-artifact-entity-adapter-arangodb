<?PHP

namespace ArangoDB\operations\common;

use ArangoDB\Initiator;
use ArangoDB\Statement;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\base\SReturn;
use ArangoDB\operations\common\interfaces\Base as BaseInterface;

/* This class is the base class for all the other classes */

abstract class Base implements BaseInterface
{
    const RANDOM_CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyz';

    protected $core;                // Initiator
    protected $skip_statement = []; // (array)
    protected $pointers = [];       // (array)
    protected $return;              // SReturn

    /**
     * Clone the object and all its properties
     * 
     * @return The object itself.
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
     * The constructor for the PHP class is responsible for setting the core and return objects
     * 
     * @param Initiator core The core object that is used to access the core functions.
     */
    
    public function __construct(Initiator $core, ...$arguments)
    {
        $this->setCore($core);
        $this->setReturn(new SReturn());
    }

    /**
     * Returns the return value of the function
     * 
     * @return The return type is SReturn.
     */
    
    public function getReturn() : SReturn
    {
        return $this->return;
    }

    /**
     * *This function adds a value to the skip_statement array.*
     * 
     * *The skip_statement array is used to skip certain values in the statement.*
     * 
     * @return The object itself.
     */
    
    public function pushStatementSkipValues(string ...$values) : self
    {
        array_push($this->skip_statement, ...$values);
        return $this;
    }

    /**
     * *This function returns an array of values that should be skipped when executing a statement.*
     * 
     * The above function is used to return an array of values that should be skipped when executing a
     * statement
     * 
     * @return An array of values that should be skipped.
     */
    
    public function getStatementSkipValues(string ...$values) : array
    {
        return $this->skip_statement;
    }

    /**
     * This function is used to set a pointer to a value
     * 
     * @param string name The name of the pointer.
     * @param value The value to be bound to the parameter.
     * @param Statement statement The statement to bind the value to.
     * 
     * @return The name of the pointer.
     */
    
    public function setPointer(string $name, $value = null, Statement $statement = null) : string
    {
        if (null === $value) while (true) if (false === in_array($randm = $this->getRandomString(), $this->pointers)) return $this->pointers[$name] = $randm;
        if (null === $statement) return $this->pointers[$name] = $value;
        $bound = $statement->bound($value);
        $bound = reset($bound);
        return $this->pointers[$name] = $bound;
    }

    /**
     * *Get a pointer to a variable in the PHP script.*
     * 
     * The function takes a string as an argument and returns a string. The string is the name of a
     * variable in the PHP script. If the variable exists in the PHP script, the function returns the
     * pointer to the variable. If the variable does not exist in the PHP script, the function creates
     * the variable and returns the pointer to the variable
     * 
     * @param string name The name of the pointer.
     * 
     * @return The pointer to the value of the variable.
     */
    
    public function getPointer(string $name) : string
    {
        return array_key_exists($name, $this->pointers) ? $this->pointers[$name] : $this->setPointer($name);
    }

    /**
     * This function sets the return value of the function
     * 
     * @param SReturn return The return value of the function.
     */
    
    protected function setReturn(SReturn $return) : void
    {
        $this->return = $return;
    }

    /**
     * The setCore function is a protected method that sets the core property of the class to the
     * Initiator class
     * 
     * @param Initiator core The core object that is used to interact with the database.
     */
    
    final protected function setCore(Initiator $core) : void
    {
        $this->core = $core;
    }

    /**
     * It returns the Initiator object.
     * 
     * @return The Initiator object.
     */
    
    final protected function getCore() : Initiator
    {
        return $this->core;
    }

    /**
     * Generate a random string of characters
     * 
     * @param int length The length of the string to be generated.
     * 
     * @return A random string of 7 characters.
     */
    
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
