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

    public function setActionOnlyEdges(bool $value = true) : self
    {
        $this->edge = $value;
        return $this;
    }

    public function getActionOnlyEdges() : bool
    {
        return $this->edge;
    }

    public function setActionPreventEmptyDocument(bool $value = true) : self
    {
        $this->null = $value;
        return $this;
    }

    public function getActionPreventEmptyDocument() : bool
    {
        return $this->null;
    }

    public function setActionPreventLoop(bool $value = true) : self
    {
        $this->loop = $value;
        return $this;
    }

    public function getActionPreventLoop() : bool
    {
        return $this->loop;
    }

    public function pushEntitySkips(Entity ...$entities) : self
    {
        array_push($this->skip_entity, ...$entities);
        return $this;
    }

    public function getEntitySkips() : array
    {
        return $this->skip_entity;
    }

    public function setEntityEnableReturns(Entity ...$entities) : self
    {
        $this->enable_entity_return = $entities;
        return $this;
    }

    public function getEntityEnableReturns() : array
    {
        return $this->enable_entity_return;
    }

    public function run() :? array
    {
        return $this->getTransaction()->commit();
    }

    public function pushTransactionsPreliminary(Transaction ...$transactions) : self
    {
        array_push($this->transactions_preliminary, ...$transactions);
        return $this;
    }

    public function pushTransactionsFinal(Transaction ...$transactions) : self
    {
        array_push($this->transactions_final, ...$transactions);
        return $this;
    }

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

    protected function setEntitySkips(Entity ...$skip_entity) : void
    {
        $this->skip_entity = $skip_entity;
    }

    protected function setTransactionsPreliminary(Transaction ...$transactions_preliminary) : void
    {
        $this->transactions_preliminary = $transactions_preliminary;
    }

    protected function getTransactionsPreliminary() : array
    {
        return $this->transactions_preliminary;
    }

    protected function setTransactionsFinal(Transaction ...$transactions_final) : void
    {
        $this->transactions_final = $transactions_final;
    }

    protected function getTransactionsFinal() : array
    {
        return $this->transactions_final;
    }

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