<?PHP

namespace ArangoDB\operations\common\base;

use ArangoDB\Statement;

/* A SReturn object is a container for a SQL statement and its bound parameters */

class SReturn
{
    const BIND_PREFIX = '$';
    const BIND_PREFIX_REGEX = '/\\' . self::BIND_PREFIX . '(\d+)/';

    protected $statement; // Statement;

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
     * The constructor for the PHP class
     */
    
    public function __construct()
    {
        $this->setStatement(new Statement());
    }

    /**
     * This function sets the query of the current query builder to the query of the given statement
     * 
     * @param Statement statement The statement to use.
     * 
     * @return The object itself.
     */
    
    public function setFromStatement(Statement $statement, ...$binds) : self
    {
        $statement_query = $statement->getQuery();
        $this->setPlain($statement_query, ...$binds);
        $this->getStatement()->addBindFromStatements($statement);
        return $this;
    }

    /**
     * * The function takes a query and binds to it. 
     * * It then replaces the bind markers with the actual values. 
     * * It then appends the query to the statement. 
     * 
     * The function is used in the following way:
     * 
     * @param string statement_query The query to be executed.
     * 
     * @return The `setPlain` method returns the `self` reference.
     */
    
    public function setPlain(string $statement_query, ...$binds) : self
    {
        $statement = $this->getStatement();
        $statement_bound = $statement->bind($binds, true);
        $statement_query = preg_replace_callback(static::BIND_PREFIX_REGEX, function ($match) use ($statement_bound) {
            return array_key_exists($match[1], $statement_bound) ? $statement_bound[$match[1]] : $match[0];
        }, $statement_query);
        $statement->append($statement_query, false);

        return $this;
    }

    /**
     * Returns the statement that was used to create this result set
     * 
     * @return The statement that was executed.
     */
    
    public function getStatement() :? Statement
    {
        return $this->statement;
    }

    /**
     * Check if a string is used in the query
     * 
     * @param string string The string to check for in the query.
     * 
     * @return A boolean value.
     */
    
    public function checkUsed(string $string) : bool
    {
        $statement_query = $this->getStatement()->getQuery();
        return false !== strpos($statement_query, $string);
    }

    /**
     * The setStatement function is used to set the statement property of the class
     * 
     * @param Statement statement The statement to be executed.
     */
    
    protected function setStatement(Statement $statement) : void
    {
        $this->statement = $statement;
    }
}