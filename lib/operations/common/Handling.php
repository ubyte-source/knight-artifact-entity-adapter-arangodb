<?PHP

namespace ArangoDB\operations\common;

use Closure;

use Knight\armor\CustomException;

use Entity\Map as Entity;

use ArangoDB\Parser;
use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Base;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\common\handling\Modifier;
use ArangoDB\operations\features\Match;

/* The Handling class is a base class for handling the data */

abstract class Handling extends Base
{
    use Modifier, Match;

    const RNEW = 'NEW';
    const ROLD = 'OLD';
    const RESPONSE = '{type: "%s", collection: "%s", document: %s}';

    protected $edge = false;                          // (bool) Action only edge
    protected $null = true;                           // (bool) Accept empty document
    protected $loop = true;                           // (bool) Prevent edge direct loop
    protected $skip_entity = [];                      // (array) Entity
    protected $enable_entity_return = [];             // (array) Entity
    protected $transactions_preliminary = [];         // (array) Transaction
    protected $transactions_final = [];               // (array) Transaction

    /**
     * * Set the action to only use edges
     * 
     * @param bool value The value to set the parameter to.
     * 
     * @return The object itself.
     */
    
    public function setActionOnlyEdges(bool $value = true) : self
    {
        $this->edge = $value;
        return $this;
    }

    /**
     * Returns a boolean value indicating whether or not the action is an edge action
     * 
     * @return The getActionOnlyEdges() method returns a boolean value.
     */
    
    public function getActionOnlyEdges() : bool
    {
        return $this->edge;
    }

    /**
     * If the document is empty, prevent the action from running
     * 
     * @param bool value The value to set the parameter to.
     * 
     * @return The object itself.
     */
    
    public function setActionPreventEmptyDocument(bool $value = true) : self
    {
        $this->null = $value;
        return $this;
    }

    /**
     * It returns a boolean value.
     * 
     * @return The value of the null property.
     */
    
    public function getActionPreventEmptyDocument() : bool
    {
        return $this->null;
    }

    /**
     * Set the value of the `loop` property
     * 
     * @param bool value The value to set the property to.
     * 
     * @return The object itself.
     */
    
    public function setActionPreventLoop(bool $value = true) : self
    {
        $this->loop = $value;
        return $this;
    }

    /**
     * Returns a boolean value indicating whether the current action is a loop
     * 
     * @return The value of the loop property.
     */
    
    public function getActionPreventLoop() : bool
    {
        return $this->loop;
    }

    /**
     * *This function adds one or more entities to the list of entities to skip when the `skipEntity`
     * function is called.*
     * 
     * @return The object itself.
     */
    
    public function pushEntitySkips(Entity ...$entities) : self
    {
        array_push($this->skip_entity, ...$entities);
        return $this;
    }

    /**
     * Returns an array of entity names that should be skipped when processing the data
     * 
     * @return An array of strings.
     */
    
    public function getEntitySkips() : array
    {
        return $this->skip_entity;
    }

    /**
     * This function sets the entities that will be returned by the `getEntity` method
     * 
     * @return The setEntityEnableReturns method returns the current instance of the class.
     */
    
    public function setEntityEnableReturns(Entity ...$entities) : self
    {
        $this->enable_entity_return = $entities;
        return $this;
    }

    /**
     * The `getEntityEnableReturns()` function returns an array of the entity types that are enabled
     * for return
     * 
     * @return The enable_entity_return property is an array of the entity names that are enabled.
     */
    
    public function getEntityEnableReturns() : array
    {
        return $this->enable_entity_return;
    }

    /**
     * This function commits the transaction
     * 
     * @return The commit.
     */
    
    public function run() :? array
    {
        return $this->getTransaction()->commit();
    }

    /**
     * *This function adds a transaction to the list of transactions that will be pushed to the
     * database.*
     * 
     * The function takes in a variable number of arguments, which are all of the type `Transaction`. 
     * 
     * @return The object itself.
     */
    
    public function pushTransactionsPreliminary(Transaction ...$transactions) : self
    {
        array_push($this->transactions_preliminary, ...$transactions);
        return $this;
    }

    /**
     * *This function adds a Transaction object to the end of the array of Transaction objects.*
     * 
     * The function is a bit more complicated than the previous ones, but it's still pretty simple. 
     * 
     * The function takes in an arbitrary number of Transaction objects, and adds them to the end of
     * the array of Transaction objects. 
     * 
     * The function returns the current instance of the class, so that it can be chained with other
     * methods.
     * 
     * @return The object itself.
     */
    
    public function pushTransactionsFinal(Transaction ...$transactions) : self
    {
        array_push($this->transactions_final, ...$transactions);
        return $this;
    }

    /**
     * This function is responsible for executing the actions of the script
     * 
     * @return The return value is a Transaction object.
     */
    
    public function getTransaction() : Transaction
    {
        $transaction = new Transaction();
        $transaction->start();

        if ($preliminary = $this->getStatementsPreliminary()) $transaction->pushStatementsPreliminary(...$preliminary);
        if ($preliminary = $this->getTransactionsPreliminary()) $this->mergeParentTransaction($transaction, 'pushStatementsPreliminary', ...$preliminary);

        $enable_entities_return = $this->getEntityEnableReturns();

        $skips_entities = $this->getEntitySkips();
        $start_vertices = $this->getCore()->getStart();
        foreach ($start_vertices as $vertex) {
            $parser = new Parser($vertex);
            $parser_data_routes = $parser->getRoutes();
            foreach ($parser_data_routes as $route) {
                $route_documents = $route->getDocuments();
                array_walk($route_documents, function (Document $document) use (&$skips_entities, $enable_entities_return, $transaction) {
                    if ($this->getActionOnlyEdges()
                        && $document->getEntity()->getType() !== Edge::TYPE) return;

                    $document_entity_skip = array_filter($skips_entities, function (Entity $entity) use ($document) {
                        return $document->getEntity()->getHash() === $entity->getHash();
                    });
                    if (false === empty($document_entity_skip)) return;
                    array_push($skips_entities, $document->getEntity());

                    $document_entity_return = array_filter($enable_entities_return, function (Entity $entity) use ($document) {
                        return $document->getEntity()->getHash() === $entity->getHash();
                    });

                    $statement = new Statement();
                    $statement->setHideResponse(empty($document_entity_return));
                    if (!!$skip_values = $this->getStatementSkipValues()) $statement->pushSkipValues(...$skip_values);

                    $document_values = $document->getValues();
                    $document_values_keys = array_keys($document_values);

                    $iterate = array_diff(Edge::DISTINCTIVE, $document_values_keys);
                    if (method_exists($this, 'before')) $this->before($statement, $document);
                    if ($document->getEntity()->getType() !== Edge::TYPE || empty($iterate)) {
                        if ($this->getActionPreventEmptyDocument() && empty($document_values)) return;
                        if ($this->getActionPreventLoop() && $document->getEntity()->getType() === Edge::TYPE) {
                            $distinctive_fields = array_fill_keys(Edge::DISTINCTIVE, null);
                            $distinctive_fields = array_intersect_key($document_values, $distinctive_fields);
                            $prevent = array_unique($distinctive_fields, SORT_STRING);
                            if (1 === count($prevent)) {
                                $distinctive_fields = $statement->bind($distinctive_fields, true);
                                $distinctive_fields = implode(chr(32) . '!=' . chr(32), $distinctive_fields);
                                $statement->append('FILTER');
                                $statement->append($distinctive_fields);
                            }
                        }
                    } else {
                        $targets = [];
                        $document_entity_direction = $document->getEntity()->getForceDirection() ?? $document->getEntity()->getDirection();
                        array_push($targets, $document->getEntity()->getFrom());
                        array_push($targets, $document->getEntity()->vertex());
                        if ($document_entity_direction === Edge::INBOUND) {
                            $targets = array_reverse($targets, false);
                            if ($iterate_reverse = array_diff(Edge::DISTINCTIVE, $iterate)) {
                                $iterate_reverse = reset($iterate_reverse);
                                $itarate_first = reset($iterate);
                                $document->setValue($itarate_first, $document_values[$iterate_reverse]);
                            }
                        }

                        foreach ($iterate as $i => $value) {
                            $targets_collection = $targets[$i]->getCollectionName();
                            $transaction->openCollectionsReadMode($targets_collection);
                            $iteration_find_variable = $this->getPointer('if' . $value);
                            $iteration_find_variable_complete = $iteration_find_variable . chr(46) . Arango::ID;
                            $statement->pushSkipValues($iteration_find_variable_complete);
                            $statement->append('FOR');
                            $statement->append($iteration_find_variable);
                            $statement->append('IN');
                            $statement->append($targets_collection);
                            $document->setValue($value, $iteration_find_variable_complete);
                            $document_used = new Document($targets[$i]);
                            if ($match = $this->matches($iteration_find_variable, $statement, $document_used)) {
                                $match_conditions = implode(chr(32) . 'AND' . chr(32), $match);
                                $statement->append('FILTER');
                                $statement->append($match_conditions);
                            }
                        }

                        if ($this->getActionPreventLoop()) {
                            $prevent = array_map(function (Entity $entity) {
                                return $entity->getCollectionName();
                            }, $targets);
                            $prevent = array_unique($prevent, SORT_STRING);
                            if (1 === count($prevent)) {
                                $document_distinctive_fields = array_fill_keys(Edge::DISTINCTIVE, null);
                                $document_values = $document->getValues();
                                $document_values_bind = array_intersect_key($document_values, $document_distinctive_fields);
                                $document_values_bind = $statement->bind($document_values_bind, true);
                                $document_values_bind = implode(chr(32) . '!=' . chr(32), $document_values_bind);
                                $statement->append('FILTER');
                                $statement->append($document_values_bind);
                            }
                        }
                    }
                    $transaction->openCollectionsWriteMode($document->getEntity()->getCollectionName());
                    $this->action($transaction, $statement, $document);
                });
            }
        }


        if ($final = $this->getTransactionsFinal()) $this->mergeParentTransaction($transaction, 'pushStatementsFinal', ...$final);
        if ($final = $this->getStatementsFinal()) $transaction->pushStatementsFinal(...$final);

        return $transaction;
    }

    /**
     * Merge the statements of a parent transaction with the statements of a child transaction
     * 
     * @param Transaction main The main transaction.
     * @param string action The method name to call on the main transaction.
     */
    
    protected function mergeParentTransaction(Transaction $main, string $action, Transaction ...$transactions) : void
    {
        if (!method_exists($this, $action)) throw new CustomException('developer/method/' . $action);
        array_walk($transactions, function (Transaction $transaction) use ($main, $action) {
            if (!!$transaction_statements_preliminary = $transaction->getStatementsPreliminary()) $main->$action(...$transaction_statements_preliminary);
            if (!!$transaction_statements = $transaction->getStatements()) $main->$action(...$transaction_statements);
            if (!!$transaction_statements_final = $transaction->getStatementsFinal()) $main->$action(...$transaction_statements_final);
            $main->openCollectionsWriteMode(...$transaction->getLockWrite());
            $main->openCollectionsReadMode(...$transaction->getLockRead()); 
        });
    }

    /**
     * *This function is used to set the entities that should be skipped when the `skip_entity`
     * function is called.*
     */
    
    protected function setEntitySkips(Entity ...$skip_entity) : void
    {
        $this->skip_entity = $skip_entity;
    }

    /**
     * The setTransactionsPreliminary function is used to set the transactions_preliminary property
     */
    
    protected function setTransactionsPreliminary(Transaction ...$transactions_preliminary) : void
    {
        $this->transactions_preliminary = $transactions_preliminary;
    }

    /**
     * This function returns the transactions_preliminary array
     * 
     * @return An array of transactions.
     */
    
    protected function getTransactionsPreliminary() : array
    {
        return $this->transactions_preliminary;
    }

    /**
     * *This function sets the transactions_final property of the class to the transactions_final
     * parameter.*
     * 
     * The next step is to create a function that will set the transactions_pending property of the
     * class
     */
    
    protected function setTransactionsFinal(Transaction ...$transactions_final) : void
    {
        $this->transactions_final = $transactions_final;
    }

    /**
     * This function returns the final array of transactions
     * 
     * @return The method returns an array of transactions.
     */
    
    protected function getTransactionsFinal() : array
    {
        return $this->transactions_final;
    }

    /**
     * If the return statement is empty, then the closure is called. Otherwise, the return statement is
     * added to the statement and the statement is appended to the query
     * 
     * @param Statement statement The statement that will be returned.
     * @param Closure closure A closure that will be called if the return value is empty.
     */
    
    protected function shouldReturn(Statement $statement, Closure $closure) : void
    {
        $return = $this->getReturn()->getStatement();
        if (0 === strlen($return->getQuery())) {
            $closure->call($this, $statement);
        } else {
            $query = $statement->addFromStatement($return);
            $statement->append($query);
        }
    }
}
