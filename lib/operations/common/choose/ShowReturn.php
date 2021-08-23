<?PHP

namespace ArangoDB\operations\common\choose;

use ArangoDB\Statement;

class ShowReturn
{
    const BIND_PREFIX = '$';
    const BIND_PREFIX_REGEX = '/\\' . self::BIND_PREFIX . '(\d+)/';

    protected $statement; // Statement;

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

    public function __construct()
    {
        $this->setStatement(new Statement());
    }

    public function setFromStatement(Statement $statement, ...$binds) : self
    {
        $statement_query = $statement->getQuery();
        $this->setPlain($statement_query, ...$binds);
        $this->getStatement()->addBindFromStatements($statement);
        return $this;
    }

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

    public function getStatement() :? Statement
    {
        return $this->statement;
    }

    public function checkUsed(string $string) : bool
    {
        $statement_query = $this->getStatement()->getQuery();
        return false !== strpos($statement_query, $string);
    }

    protected function setStatement(Statement $statement) : void
    {
        $this->statement = $statement;
    }
}