<?PHP

namespace ArangoDB;

use ArangoDB\common\Configuration;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\base\Document;

use ArangoDBClient\Connection;
use ArangoDBClient\Statement as ClientStatement;
use ArangoDBClient\ServerException;

/* The Statement class is used to build a query that can be executed against the ArangoDB server */

class Statement
{
    const BIND_PREFIX = 'X';
    const BIND_PREFIX_VARIABLE = '\\$';
    const STRING_KEYS = [
        Edge::FROM,
        Edge::TO,
        Arango::KEY,
        Arango::ID,
        Arango::REV
    ];

    protected $query = '';                 // (string)
    protected $type = 'special';           // (string)
    protected $bind = [];                  // (array)
    protected $skip_values = [];           // (array)
    protected $expect;                     // (int)
    protected $hide_response = false;      // (bool)
    protected $exception_message;          // (string)

    /**
     * Set the number of rows that are expected to be returned by the query
     * 
     * @param int expect The number of times the test is expected to pass.
     * 
     * @return The object itself.
     */
    
    public function setExpect(int $expect) : self
    {
        $this->expect = abs($expect);
        return $this;
    }

    /**
     * Get the value of the `expect` property
     * 
     * @return The expect property.
     */
    
    public function getExpect() :? int
    {
        return $this->expect;
    }

    /**
     * * Set the hide_response property to the value of the hide_response parameter
     * 
     * @param bool hide_response If set to true, the response will be hidden from the user.
     * 
     * @return The object itself.
     */
    
    public function setHideResponse(bool $hide_response) : self
    {
        $this->hide_response = $hide_response;
        return $this;
    }

    /**
     * Returns the value of the `hide_response` property
     * 
     * @return The getHideResponse() method returns a boolean value.
     */
    
    public function getHideResponse() : bool
    {
        return $this->hide_response;
    }

    /**
     * * Set the type of the document
     * 
     * @param Document data The data to set the type to.
     * 
     * @return The object itself.
     */
    
    public function setType(Document $data) : self
    {
        $this->type = $data->getEntity()->getType();
        return $this;
    }

    /**
     * Get the type of the current object
     * 
     * @return The type of the object.
     */
    
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Append a string to the query
     * 
     * @param string string The string to append to the query.
     * @param bool whitespace If true, appends a space after the string.
     * 
     * @return The query builder object.
     */
    
    public function append(string $string, bool $whitespace = true, ?string ...$data) : self
    {

        $bound = $this->bound(...$data);
        $string_expression = chr(47) . static::BIND_PREFIX_VARIABLE . chr(40) . '\d+' . chr(41) . chr(47);
        $string_expression = preg_replace_callback($string_expression, function ($match) use ($bound) {
            return array_key_exists($match[1], $bound) ? $bound[$match[1]] : $match[0];
        }, $string);

        $this->query .= $string_expression;
        if (true === $whitespace) $this->query .= chr(32);
        return $this;
    }

    /**
     * Overwrite the query that will be used to generate the SQL
     * 
     * @param string query The query to run.
     * 
     * @return The object itself.
     */
    
    public function overwrite(string $query) : self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * This function binds the data to the query
     * 
     * @param array data The data to bind to the query.
     * @param bool associative If true, the bound data will be returned as an associative array.
     * 
     * @return An array of bound values.
     */
    
    public function bind(array $data, bool $associative = false) : array
    {
        $bound = [];
        foreach ($data as $key => $value) {
            $skip = in_array($value, $this->getSkipValues(), true);
            $name = $skip ? $value : array_search($value, $this->bind, true);
            if (false === $name) {
                $bind_increment = static::getBindIncrementKey();
                $name = static::BIND_PREFIX . $bind_increment;
                $this->bind[$name] = in_array($key, static::STRING_KEYS) ? strval($value) : $value;
            }

            if (false === $skip) $name = chr(64) . $name;
            array_push($bound, $name);
        }

        if (false === $associative) return $bound;
        $bound = array_combine(array_keys($data), $bound);
        return $bound;
    }

    /**
     * This function binds the parameters to the SQL statement and returns the bound parameters
     * 
     * @return An array of bound parameters.
     */
    
    public function bound(...$data) : array
    {
        $bound = $this->bind($data, true);
        return $bound;
    }

    /**
     * Add a list of statements to the current statement
     * 
     * @return The object itself.
     */
    
    public function addBindFromStatements(Statement ...$statements) : self
    {
        array_walk($statements, function (Statement $statement) {
            $this->bind = array_merge($statement->getBind(), $this->bind);
        });
        return $this;
    }

    /**
     * Given a prepared statement, return the query with the bound values replaced with the actual
     * values
     * 
     * @param Statement statement The statement to add to the query.
     * 
     * @return The query with the bound values replaced with the values from the statement.
     */
    
    public function addFromStatement(Statement $statement) : string
    {
        $bind = $statement->getBind();
        $bind_keys = array_keys($bind);
        $bind_keys = preg_filter('/' . static::BIND_PREFIX . '(\d+)' . '/', '$1', $bind_keys);

        $replace = $this->bind($bind, true);
        $replace = array_combine($bind_keys, $replace);

        $query = $statement->getQuery();
        $query = preg_replace_callback('/' . chr(64) . static::BIND_PREFIX . '(\d+)' . '/', function ($match) use ($replace) {
            return array_key_exists($match[1], $replace) ? $replace[$match[1]] : $match[0];
        }, $query);
        return $query;
    }

    /**
     * Returns the bind array
     * 
     * @return An array of bind values.
     */
    
    public function getBind() : array
    {
        return $this->bind;
    }

    /**
     * Add values to the skip_values array
     * 
     * @return The object itself.
     */
    
    public function pushSkipValues(string ...$strings) : self
    {
        $skip_values = $this->getSkipValues();
        $strings = array_filter($strings, function (string $string) use ($skip_values) {
            return false === in_array($string, $skip_values);
        });
        array_push($this->skip_values, ...$strings);
        return $this;
    }

    /**
     * Returns an array of values to skip when inserting data
     * 
     * @return An array of values that should be skipped.
     */
    
    public function getSkipValues() : array
    {
        return $this->skip_values;
    }

    /**
     * * If the filter is set to true and the document has no values, return null.
     * * Otherwise, bind the document's values and return the bind string
     * 
     * @param bool filter If true, only bind values that are not empty.
     * @param Document document The document to bind.
     * 
     * @return The bind values for the document.
     */
    
    public function bindDocument(bool $filter, Document $document) :? string
    {
        $values = $document->getValues();
        if (true === $filter
            && empty($values)) return null;

        $bind = $this->bind($values, true);
        array_walk($bind, function (&$value, $key) {
            $value = $key . chr(58) . chr(32) . $value;
        });

        $bind = implode(chr(44) . chr(32), $bind);
        $bind = chr(123) .  $bind . chr(125);

        return $bind;
    }

    /**
     * Given a list of documents, return a string that represents a list of documents that can be used
     * in a SQL query
     * 
     * @param bool filter If true, only documents that have a value for the field will be bound.
     * 
     * @return The bind string for the documents.
     */
    
    public function bindDocuments(bool $filter, Document ...$documents) :? string
    {
        $bind = array_map(function (Document $document) use ($filter) {
            return $this->bindDocument($filter, $document);
        }, $documents);

        $bind = array_filter($bind);
        if (true === $filter
            && empty($bind)) return null;

        $bind = array_unique($bind, SORT_STRING);
        $bind = implode(chr(44) . chr(32), $bind);
        $bind = chr(91) . $bind . chr(93);

        return $bind;
    }

    /**
     * This function takes an array of data and returns a string of the data separated by commas and
     * spaces
     * 
     * @param array data The data to bind to the query.
     * 
     * @return The string `[1, 2, 3]`
     */
    
    public function bindArray(array $data) : string
    {
        return chr(91) . implode(chr(44) . chr(32), $this->bind($data)) . chr(93);
    }

    /**
     * Execute a query against the ArangoDB server
     * 
     * @return The result of the query.
     */
    
    public function execute() : array
    {
        try {
            ServerException::disableLogging();

            $arango_connection_configuration = Configuration::get();
            $arango_connection = new Connection($arango_connection_configuration);

            $data = [
                'query' => $this->getQuery(),
                '_flat' => true
            ];
            if (!!$bind = $this->getBind()) $data['bindVars'] = &$bind;

            $statement = new ClientStatement($arango_connection, $data);

            $response = $statement->execute()->getAll();
            $response = array_filter($response);
            return $response;
        } catch (ServerException $exception) {
            return array();
        }
    }

    /**
     * Returns the query string
     * 
     * @return The query string.
     */
    
    public function getQuery() : string
    {
        return trim($this->query, chr(32));
    }

    /**
     * * Set the exception message
     * 
     * @param string message The message to be displayed in the exception.
     * 
     * @return The object itself.
     */
    
    public function setExceptionMessage(string $message) : self
    {
        $this->exception_message = $message;
        return $this;
    }

    /**
     * Returns the exception message if it exists, otherwise returns null
     * 
     * @return The exception message.
     */
    
    public function getExceptionMessage() :? string
    {
        return $this->exception_message;
    }

    /**
     * *This function returns a unique integer for each call.*
     * 
     * @return The increment key.
     */
    
    protected function getBindIncrementKey() : int
    {
        static $increment;
        if (null === $increment) $increment = 0;
        return $increment++;
    }
}