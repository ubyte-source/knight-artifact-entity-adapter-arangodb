<?PHP

namespace ArangoDB;

use ArangoDB\common\Configuration;
use ArangoDB\entity\Edge;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\base\Document;

use ArangoDBClient\Connection;
use ArangoDBClient\Statement as ClientStatement;
use ArangoDBClient\ServerException;

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

    public function setExpect(int $expect) : self
    {
        $this->expect = abs($expect);
        return $this;
    }

    public function getExpect() :? int
    {
        return $this->expect;
    }

    public function setHideResponse(bool $hide_response) : self
    {
        $this->hide_response = $hide_response;
        return $this;
    }

    public function getHideResponse() : bool
    {
        return $this->hide_response;
    }

    public function setType(Document $data) : self
    {
        $this->type = $data->getEntity()->getType();
        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

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

    public function overwrite(string $query) : self
    {
        $this->query = $query;
        return $this;
    }

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

    public function bound(...$data) : array
    {
        $bound = $this->bind($data, true);
        return $bound;
    }

    public function addBindFromStatements(Statement ...$statements) : self
    {
        array_walk($statements, function (Statement $statement) {
            $this->bind = array_merge($statement->getBind(), $this->bind);
        });
        return $this;
    }

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

    public function getBind() : array
    {
        return $this->bind;
    }

    public function pushSkipValues(string ...$strings) : self
    {
        $skip_values = $this->getSkipValues();
        $strings = array_filter($strings, function (string $string) use ($skip_values) {
            return false === in_array($string, $skip_values);
        });
        array_push($this->skip_values, ...$strings);
        return $this;
    }

    public function getSkipValues() : array
    {
        return $this->skip_values;
    }

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

    public function bindArray(array $data) : string
    {
        return chr(91) . implode(chr(44) . chr(32), $this->bind($data)) . chr(93);
    }

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

    public function getQuery() : string
    {
        return trim($this->query, chr(32));
    }

    public function setExceptionMessage(string $message) : self
    {
        $this->exception_message = $message;
        return $this;
    }

    public function getExceptionMessage() :? string
    {
        return $this->exception_message;
    }

    protected function getBindIncrementKey() : int
    {
        static $increment;
        if (null === $increment) $increment = 0;
        return $increment++;
    }
}