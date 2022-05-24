<?PHP

namespace ArangoDB\operations\common\handling;

use ArangoDB\Statement;

/* The `trait` keyword is used to create a trait. A trait is a collection of methods that can be used
as if they were part of the class in which they are included. */

trait Modifier
{
    protected $statements_preliminary = []; // (array) Statement
    protected $statements_final = [];       // (array) Statement

    /**
     * *This function pushes a statement or statements onto the array of statements that will be
     * executed.*
     * 
     * The function is called `pushStatementsPreliminary` and it takes an arbitrary number of
     * arguments. The arguments are the statements that will be pushed onto the array of statements
     * that will be executed
     * 
     * @return Nothing.
     */
    
    public function pushStatementsPreliminary(Statement ...$statements) : self
    {
        array_push($this->statements_preliminary, ...$statements);
        return $this;
    }

    /**
     * Returns the statements that were executed during the current transaction
     * 
     * @return An array of statements.
     */
    
    public function getStatementsPreliminary() : array
    {
        return $this->statements_preliminary;
    }

    /**
     * Add statements to the final array
     * 
     * @return The object itself.
     */
    
    public function pushStatementsFinal(Statement ...$statements) : self
    {
        array_push($this->statements_final, ...$statements);
        return $this;
    }

    /**
     * Returns the final array of statements
     * 
     * @return An array of statements.
     */
    
    public function getStatementsFinal() : array
    {
        return $this->statements_final;
    }

    /**
     * This function sets the statements that will be executed before the transaction is started
     */
    
    protected function setTransactionsPreliminary(Statement ...$statements_preliminary) : void
    {
        $this->statements_preliminary = $statements_preliminary;
    }

    /**
     * This function sets the final statements that will be executed after the transaction has been
     * committed
     */
    
    protected function setTransactionsFinal(Statement ...$statements_final) : void
    {
        $this->statements_final = $statements_final;
    }
}
