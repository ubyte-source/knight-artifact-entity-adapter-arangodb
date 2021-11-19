<?PHP

namespace ArangoDB;

use Knight\armor\Output;
use Knight\armor\CustomException;

use ArangoDB\Statement;
use ArangoDB\common\Configuration;
use ArangoDB\operations\common\handling\Modifier;

use ArangoDBClient\Connection;
use ArangoDBClient\ServerException;
use ArangoDBClient\Transaction as ClientTransaction;

class Transaction
{
    use Modifier;

    protected $edge_first = false;                      // (bool)
    protected $statements = [];                         // (array)
    protected $writer = [];                             // (array)
    protected $reader = [];                             // (array)
    protected $exception_message_default = 'Rollback!'; // (string)

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
        $this->start();
    }

    public function start() : self
    {
        $this->removeAllStatements();

        if (method_exists($this, 'initialize')) $this->initialize();

        $this->initializeLockWrite();
        $this->initializeLockRead();
        return $this;
    }

    public function pushStatements(Statement ...$statements) : self
    {
        array_push($this->statements, ...$statements);
        return $this;
    }

    public function getStatements() : array
    {
        return $this->statements;
    }

    public function openCollectionsReadMode(string ...$collections) : self
    {
        $reader = $this->getLockRead();
        $collections_filtered = array_diff($collections, $reader);
        array_push($this->reader, ...$collections_filtered);
        return $this;
    }

    public function getLockRead() : array
    {
        return $this->reader;
    }

    public function openCollectionsWriteMode(string ...$collections) : self
    {
        $writer = $this->getLockWrite();
        $collections_filtered = array_diff($collections, $writer);
        array_push($this->writer, ...$collections_filtered);
        return $this;
    }

    public function getLockWrite() : array
    {
        return $this->writer;
    }

    public function setEdgeFirst(bool $use = true) : self
    {
        $this->edge_first = $use;
        return $this;
    }

    public function setExceptionMessageDefault(string $message) : Statement
    {
        $this->exception_message_default = $message;
        return $this;
    }

    public function getExceptionMessageDefault() : string
    {
        return $this->exception_message_default;
    }

    public function commit() :? array
    {
        $statements = $this->getStatements();
        if (0 === count($statements)) throw new CustomException('developer/statements');

        $first = true === $this->getEdgeFirst() ? 'edge' : 'vertex';
        usort($statements, function ($a, $b) use ($first) {
            $type = $b->getType();
            return $type === $first;
        });

        $statements_preliminary = $this->getStatementsPreliminary();
        $statements_final = $this->getStatementsFinal();
        $statements = array_merge($statements_preliminary, $statements, $statements_final);
        $statements = array_unique($statements, SORT_REGULAR);
        $statements = array_values($statements);
        unset($statements_preliminary, $statements_final);

        $javascript = 'function () {';
        $javascript .= 'var db = require("@arangodb").db, response = [];';

        $exceptions_message_accepted = [];
        foreach ($statements as $i => $statement) {
            $arango_query_language = $statement->getQuery();
            $arango_query_language = addslashes($arango_query_language);

            $variable_statement = 'statement_' . $i;
            $javascript .= 'let' . chr(32) . $variable_statement . chr(32) . '= db._createStatement({';
            $javascript .= 'query:"' . $arango_query_language . '",';
            if (!!$bind = $statement->getBind()) {
                $json = Output::json($bind, JSON_UNESCAPED_SLASHES);
                $javascript .= 'bindVars:' . $json;
                unset($json);
            }
            $javascript .= '});';
            $variable_response = $variable_statement . '_response';
            $javascript .= 'let' . chr(32) . $variable_response . chr(32) . '=' . chr(32);
            $javascript .= $variable_statement;
            $javascript .= chr(46) . 'execute()';
            $javascript .= chr(46) . 'toArray();';

            $expect = $statement->getExpect();
            if (null !== $expect) {
                $expect_condition = strval($expect) . chr(32) . '!==';

                $exception_message = $statement->getExceptionMessage();
                if (null === $exception_message) $exception_message = $this->getExceptionMessageDefault();

                $exception_message_hash = md5($exception_message);
                array_push($exceptions_message_accepted, $exception_message_hash);
                $exception_message = addslashes($exception_message);

                $javascript .= 'if (' . $expect_condition . chr(32) . $variable_response . chr(46) . 'length) throw "' . $exception_message . '";';
            }

            $hide_response = $statement->getHideResponse();
            if (false === $hide_response) {
                $javascript .= $variable_response;
                $javascript .= chr(46) . 'forEach(function (item) {';
                $javascript .= 'response.push(item);';
                $javascript .= '});';
            }
        }

        $javascript .= 'return response;}';
        $writer = $this->getLockWrite();
        $reader = $this->getLockRead();
        $lock = [
            'write' => &$writer,
            'read' => &$reader
        ];

        try {
            ServerException::disableLogging();

            $arango_connection_configuration = Configuration::get();
            $arango_connection = new Connection($arango_connection_configuration);

            $transaction = new ClientTransaction($arango_connection);
            $transaction->setAction($javascript);
            $transaction->setCollections($lock);

            $execute = $transaction->execute();

            $this->start();
        } catch (ServerException $exception) {
            $this->start();

            $exception_message = $exception->getMessage();
            $exception_message_hash = md5($exception_message);
            if (false === in_array($exception_message_hash, $exceptions_message_accepted)) return null;

            Output::concatenate('notice', $exception_message);
            Output::print(false);
        }

        return $execute;
    }

    protected function getEdgeFirst() : bool
    {
        return $this->edge_first;
    }

    protected function initializeLockRead() : void
    {
        $this->reader = [];
    }

    protected function initializeLockWrite() : void
    {
        $this->writer = [];
    }

    protected function removeAllStatements() : void
    {
        $this->statements = [];
    }
}