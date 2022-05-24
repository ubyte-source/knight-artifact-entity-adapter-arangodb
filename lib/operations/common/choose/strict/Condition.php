<?PHP

namespace ArangoDB\operations\common\choose\strict;

use ArangoDB\Statement;
use ArangoDB\operations\common\choose\strict\Hop;

/* A condition is a set of hops and statements */

class Condition
{

    protected $hops = [];             // (array) Hop
    protected $statements = [];       // (array) Statement
    protected $number = [];           // (array)
    protected $deterministic = false; // (bool)

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
     * * Sets the deterministic property of the function
     * 
     * @param bool deterministic If true, the random number generator will be seeded with a fixed
     * value. This is useful for reproducing results.
     * 
     * @return The object itself.
     */
    
    public function setDeterministic(bool $deterministic = true) : self
    {
        $this->deterministic = $deterministic;
        return $this;
    }

    /**
     * Returns the value of the `deterministic` property
     * 
     * @return The value of the `deterministic` property.
     */
    
    public function getDeterministic() : bool
    {
        return $this->deterministic;
    }

    /**
     * Add a statement to the list of statements
     * 
     * @param Statement statement The statement to add to the list of statements.
     * 
     * @return The object itself.
     */
    
    public function addStatement(Statement $statement) : self
    {
        array_push($this->statements, $statement);
        return $this;
    }

    /**
     * Returns an array of all the statements in the query
     * 
     * @return An array of statements.
     */
    
    public function getStatements() : array
    {
        return $this->statements;
    }

    /**
     * Add a number of matches to the array of matches
     * 
     * @return The object itself.
     */
    
    public function addMatches(int ...$number) : self
    {
        array_push($this->number, ...$number);
        return $this;
    }

    /**
     * Returns the number of matches
     * 
     * @return An array of integers.
     */
    
    public function getMatches() : array
    {
        return $this->number;
    }

    /**
     * Add a Hop to the Recipe
     * 
     * @return The object itself.
     */
    
    public function addHops(Hop ...$hops) : self
    {
        array_push($this->hops, ...$hops);
        return $this;
    }

    /**
     * Get the hops of the beer
     * 
     * @return An array of Hop objects.
     */
    
    public function getHops() : array
    {
        return $this->hops;
    }
}
