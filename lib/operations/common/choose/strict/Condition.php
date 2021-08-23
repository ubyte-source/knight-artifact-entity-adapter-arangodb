<?PHP

namespace ArangoDB\operations\common\choose\strict;

use ArangoDB\Statement;
use ArangoDB\operations\common\choose\strict\Hop;

class Condition
{

    protected $hops = [];             // (array) Hop
    protected $statements = [];       // (array) Statement
    protected $number = [];           // (array)
    protected $deterministic = false; // (bool)

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

    public function setDeterministic(bool $deterministic = true) : self
    {
        $this->deterministic = $deterministic;
        return $this;
    }

    public function getDeterministic() : bool
    {
        return $this->deterministic;
    }

    public function addStatement(Statement $statement) : self
    {
        array_push($this->statements, $statement);
        return $this;
    }

    public function getStatements() : array
    {
        return $this->statements;
    }

    public function addMatches(int ...$number) : self
    {
        array_push($this->number, ...$number);
        return $this;
    }

    public function getMatches() : array
    {
        return $this->number;
    }

    public function addHops(Hop ...$hops) : self
    {
        array_push($this->hops, ...$hops);
        return $this;
    }

    public function getHops() : array
    {
        return $this->hops;
    }
}