<?PHP

namespace ArangoDB\operations\common\handling;

use ArangoDB\Statement;

trait Modifier
{
    protected $statements_preliminary = []; // (array) Statement
    protected $statements_final = [];       // (array) Statement

    public function pushStatementsPreliminary(Statement ...$statements) : self
    {
        array_push($this->statements_preliminary, ...$statements);
        return $this;
    }

    public function getStatementsPreliminary() : array
    {
        return $this->statements_preliminary;
    }

    public function pushStatementsFinal(Statement ...$statements) : self
    {
        array_push($this->statements_final, ...$statements);
        return $this;
    }

    public function getStatementsFinal() : array
    {
        return $this->statements_final;
    }

    protected function setTransactionsPreliminary(Statement ...$statements_preliminary) : void
    {
        $this->statements_preliminary = $statements_preliminary;
    }

    protected function setTransactionsFinal(Statement ...$statements_final) : void
    {
        $this->statements_final = $statements_final;
    }
}