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

/* The Transaction class is a class that allows you to execute a series of statements in a single
transaction */

class Transaction
{
    use Modifier;

    protected $edge_first = false;                      // (bool)
    protected $statements = [];                         // (array)
    protected $writer = [];                             // (array)
    protected $reader = [];                             // (array)
    protected $exception_message_default = 'Rollback!'; // (string)

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
     * This function is called when the class is instantiated
     */
    
    public function __construct()
    {
        $this->start();
    }

    /**
     * This function starts the database connection and locks the database for writing
     * 
     * @return The object itself.
     */
    
    public function start() : self
    {
        $this->removeAllStatements();

        if (method_exists($this, 'initialize')) $this->initialize();

        $this->initializeLockWrite();
        $this->initializeLockRead();
        return $this;
    }

    /**
     * Add a statement to the end of the list of statements
     * 
     * @return The object itself.
     */
    
    public function pushStatements(Statement ...$statements) : self
    {
        array_push($this->statements, ...$statements);
        return $this;
    }

    /**
     * Returns the statements that were executed during the transaction
     * 
     * @return An array of statements.
     */
    
    public function getStatements() : array
    {
        return $this->statements;
    }

    /**
     * Open the specified collections in read mode
     * 
     * @return The object itself.
     */
    
    public function openCollectionsReadMode(string ...$collections) : self
    {
        $reader = $this->getLockRead();
        $collections_filtered = array_diff($collections, $reader);
        array_push($this->reader, ...$collections_filtered);
        return $this;
    }

    /**
     * Returns the current reader lock
     * 
     * @return An array of locks that are currently held for reading.
     */
    
    public function getLockRead() : array
    {
        return $this->reader;
    }

    /**
     * Open the specified collections in write mode
     * 
     * @return The object itself.
     */
    
    public function openCollectionsWriteMode(string ...$collections) : self
    {
        $writer = $this->getLockWrite();
        $collections_filtered = array_diff($collections, $writer);
        array_push($this->writer, ...$collections_filtered);
        return $this;
    }

    /**
     * Returns the writer lock
     * 
     * @return An array of the lock types that are currently held by the writer.
     */
    
    public function getLockWrite() : array
    {
        return $this->writer;
    }

    /**
     * * Set the edge_first property to the given value
     * 
     * @param bool use Whether or not to use the first and last nodes in the path.
     * 
     * @return The object itself.
     */
    
    public function setEdgeFirst(bool $use = true) : self
    {
        $this->edge_first = $use;
        return $this;
    }

    /**
     * Set the default exception message
     * 
     * @param string message The message to be displayed when the exception is thrown.
     * 
     * @return The object itself.
     */
    
    public function setExceptionMessageDefault(string $message) : Statement
    {
        $this->exception_message_default = $message;
        return $this;
    }

    /**
     * Returns the default exception message
     * 
     * @return The exception message default.
     */
    
    public function getExceptionMessageDefault() : string
    {
        return $this->exception_message_default;
    }

    /**
     * It executes the statements in the transaction
     */
    
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

    /**
     * Returns the value of the edge_first property
     * 
     * @return The value of the protected member variable `edge_first`.
     */
    
    protected function getEdgeFirst() : bool
    {
        return $this->edge_first;
    }

    /**
     * Initialize the reader array
     */
    
    protected function initializeLockRead() : void
    {
        $this->reader = [];
    }

    /**
     * Initialize the writer array
     */
    
    protected function initializeLockWrite() : void
    {
        $this->writer = [];
    }

    /**
     * Remove all statements from the statements array
     */
    
    protected function removeAllStatements() : void
    {
        $this->statements = [];
    }
}
