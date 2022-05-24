# Documentation knight-artifact-entity-adapter-arangodb

> Knight PHP library to build query in ArangoDB.

**NOTE:** This repository is part of [Knight](https://github.com/energia-source/knight). Any
support requests, bug reports, or development contributions should be directed to
that project.

## Structure

library:
- [ArangoDB\adapters\map](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/adapters/map)
- [ArangoDB\adapters](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/adapters)
- [ArangoDB\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/common)
- [ArangoDB\entity\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/entity/common)
- [ArangoDB\entity](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/entity)
- [ArangoDB\operations\features](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/features)
- [ArangoDB\operations\common\base](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/base)
- [ArangoDB\operations\common\choose\strict](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/choose/strict)
- [ArangoDB\operations\common\choose](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/choose)
- [ArangoDB\operations\common\handling](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common/handling)
- [ArangoDB\operations\common](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations/common)
- [ArangoDB\operations](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/operations)
- [ArangoDB\parser](https://github.com/energia-source/knight-artifact-entity-adapter-arangodb/tree/main/lib/parser)
- [ArangoDB](https://github.com/energia-source/knight-knight-artifact-entity-adapter-arangodb/blob/main/lib)

<br>

#### ***Class ArangoDB\Initiator usable methods***

##### `protected function __construct()`

This is the constructor function

##### `public static function getNamespaceName() : string`

Get the namespace name of the class

 * **Returns:** The namespace name of the class.

##### `public static function entityAttachAdapter(Arango $entity, string $adapter_name) : void`

Attach an adapter to an entity

 * **Parameters:**
   * `Arango` — The entity to attach the adapter to.
   * `string` — The name of the adapter to use.

##### `public function __clone()`

Clone the object and all its properties

 * **Returns:** The object is being cloned and the object is being returned.

##### `public function __call(string $method, array $arguments) : Base`

If the method name is not a valid operation, throw an exception

 * **Parameters:**
   * `string` — The name of the method that was called.
   * `array` — The arguments passed to the method.

     <p>
 * **Returns:** The `__call` method returns an instance of the `Base` class.

##### `public static function start(Vertex ...$vertices) : self`

Creates a new instance of the class and adds the given vertices to the start array

 * **Returns:** The instance of the class.

##### `public function setUseAdapter(bool $adapter = true) : self`

If the adapter is set to true, then the adapter manager is called on each vertex

 * **Parameters:** `bool` — If true, the adapter will be used.

     <p>
 * **Returns:** The object itself.

##### `public function getUseAdapter() : bool`

Returns the value of the `adapter` property

 * **Returns:** The value of the `adapter` property.

##### `protected function push(Vertex ...$vertices) : void`

* Push a vertex to the start of the list

 * **Returns:** `Nothing.`

##### `public function getStart() : array`

Returns the start of the current iteration

 * **Returns:** An array of integers.

##### `public function begin() :? Vertex`

Return the first vertex in the graph

 * **Returns:** The first vertex in the start list.

##### `public function reset() : self`

Reset the start array

 * **Returns:** The object itself.

<br>

#### ***Class ArangoDB\Parser usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** The object is being returned.

##### `public function __construct(Vertex ...$vertices)`

* The constructor takes a list of vertices and creates a route object. * The route object is created by traversing the edges of the first vertex. * The traversal is done by creating a list of edges and traversing them.

 * **Returns:** The `Route` object.

##### `public function getTargetsEdge() : array`

Returns an array of the target edges

 * **Returns:** An array of edges.

##### `public function getTargetsVertex() : array`

Get all the targets of the edges in the graph

 * **Returns:** An array of Vertex objects.

##### `public function getTargetsVertexName() : array`

Get the names of all the vertices that are targets of the edges in the graph

 * **Returns:** The names of the vertices that are targets of the edges in the graph.

##### `public function getEdges() : array`

Return an array of all the edges in the graph

 * **Returns:** An array of Edge objects.

##### `public function getEdgesName() : array`

Returns an array of the names of all the edges in the graph

 * **Returns:** An array of strings.

##### `public function getEdgesNameWithDirection() : array`

Get the name of the edges with the direction

 * **Returns:** An array of edge names with directions.

##### `public function getRoutes() : array`

Returns an array of all the routes that have been added to the router

 * **Returns:** An array of Route objects.

##### `public function getWithCollections() : array`

Get the collection names of the vertex and all the collections that are connected to it

 * **Returns:** The collection names of the start vertex, the end vertices of each edge, and the start
     vertex of each edge.

<br>

#### ***Class ArangoDB\Statement usable methods***

##### `public function setExpect(int $expect) : self`

Set the number of rows that are expected to be returned by the query

 * **Parameters:** `int` — The number of times the test is expected to pass.
 * **Returns:** The object itself.

##### `public function getExpect() :? int`

Get the value of the `expect` property

 * **Returns:** The expect property.

##### `public function setHideResponse(bool $hide_response) : self`

* Set the hide_response property to the value of the hide_response parameter

 * **Parameters:** `bool` — If set to true, the response will be hidden from the user.
 * **Returns:** The object itself.

##### `public function getHideResponse() : bool`

Returns the value of the `hide_response` property

 * **Returns:** The getHideResponse() method returns a boolean value.

##### `public function setType(Document $data) : self`

* Set the type of the document

 * **Parameters:** `Document` — The data to set the type to.
 * **Returns:** The object itself.

##### `public function getType() : string`

Get the type of the current object

 * **Returns:** The type of the object.

##### `public function append(string $string, bool $whitespace = true, ?string ...$data) : self`

Append a string to the query

 * **Parameters:**
   * `string` — The string to append to the query.
   * `bool` — If true, appends a space after the string.
 * **Returns:** The query builder object.

##### `public function overwrite(string $query) : self`

Overwrite the query that will be used to generate the SQL

 * **Parameters:** `string` — The query to run.
 * **Returns:** The object itself.

##### `public function bind(array $data, bool $associative = false) : array`

This function binds the data to the query

 * **Parameters:**
   * `array` — The data to bind to the query.
   * `bool` — If true, the bound data will be returned as an associative array.

     <p>
 * **Returns:** An array of bound values.

##### `public function bound(...$data) : array`

This function binds the parameters to the SQL statement and returns the bound parameters

 * **Returns:** An array of bound parameters.

##### `public function addBindFromStatements(Statement ...$statements) : self`

Add a list of statements to the current statement

 * **Returns:** The object itself.

##### `public function addFromStatement(Statement $statement) : string`

Given a prepared statement, return the query with the bound values replaced with the actual values

 * **Parameters:** `Statement` — The statement to add to the query.

     <p>
 * **Returns:** The query with the bound values replaced with the values from the statement.

##### `public function getBind() : array`

Returns the bind array

 * **Returns:** An array of bind values.

##### `public function pushSkipValues(string ...$strings) : self`

Add values to the skip_values array

 * **Returns:** The object itself.

##### `public function getSkipValues() : array`

Returns an array of values to skip when inserting data

 * **Returns:** An array of values that should be skipped.

##### `public function bindDocument(bool $filter, Document $document) :? string`

* If the filter is set to true and the document has no values, return null. * Otherwise, bind the document's values and return the bind string

 * **Parameters:**
   * `bool` — If true, only bind values that are not empty.
   * `Document` — The document to bind.
 * **Returns:** The bind values for the document.

##### `public function bindDocuments(bool $filter, Document ...$documents) :? string`

Given a list of documents, return a string that represents a list of documents that can be used in a SQL query

 * **Parameters:** `bool` — If true, only documents that have a value for the field will be bound.
 * **Returns:** The bind string for the documents.

##### `public function bindArray(array $data) : string`

This function takes an array of data and returns a string of the data separated by commas and spaces

 * **Parameters:** `array` — The data to bind to the query.
 * **Returns:** The string `[1, 2, 3]`

##### `public function execute() : array`

Execute a query against the ArangoDB server

 * **Returns:** The result of the query.

##### `public function getQuery() : string`

Returns the query string

 * **Returns:** The query string.

##### `public function setExceptionMessage(string $message) : self`

* Set the exception message

 * **Parameters:** `string` — The message to be displayed in the exception.
 * **Returns:** The object itself.

##### `public function getExceptionMessage() :? string`

Returns the exception message if it exists, otherwise returns null

 * **Returns:** The exception message.

<br>

#### ***Class ArangoDB\Transaction usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** `Nothing.` — 

##### `public function __construct()`

This function is called when the class is instantiated

##### `public function start() : self`

This function starts the database connection and locks the database for writing

 * **Returns:** The object itself.

##### `public function pushStatements(Statement ...$statements) : self`

Add a statement to the end of the list of statements

 * **Returns:** The object itself.

##### `public function getStatements() : array`

Returns the statements that were executed during the transaction

 * **Returns:** An array of statements.

##### `public function openCollectionsReadMode(string ...$collections) : self`

Open the specified collections in read mode

 * **Returns:** The object itself.

##### `public function getLockRead() : array`

Returns the current reader lock

 * **Returns:** An array of locks that are currently held for reading.

##### `public function openCollectionsWriteMode(string ...$collections) : self`

Open the specified collections in write mode

 * **Returns:** The object itself.

##### `public function getLockWrite() : array`

Returns the writer lock

 * **Returns:** An array of the lock types that are currently held by the writer.

##### `public function setEdgeFirst(bool $use = true) : self`

* Set the edge_first property to the given value

 * **Parameters:** `bool` — Whether or not to use the first and last nodes in the path.
 * **Returns:** The object itself.

##### `public function setExceptionMessageDefault(string $message) : Statement`

Set the default exception message

 * **Parameters:** `string` — The message to be displayed when the exception is thrown.
 * **Returns:** The object itself.

##### `public function getExceptionMessageDefault() : string`

Returns the default exception message

 * **Returns:** The exception message default.

##### `public function commit() :? array`

It executes the statements in the transaction

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
