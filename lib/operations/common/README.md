# Documentation knight-artifact-entity-adapter-arangodb

Knight PHP library to build query in ArangoDB.

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

#### ***Class ArangoDB\operations\common\Base usable methods***

##### `public function __clone()`

Clone the object and all its properties

 * **Returns:** The object itself.

##### `public function __construct(Initiator $core, ...$arguments)`

The constructor for the PHP class is responsible for setting the core and return objects

 * **Parameters:** `Initiator` — The core object that is used to access the core functions.

##### `public function getReturn() : SReturn`

Returns the return value of the function

 * **Returns:** The return type is SReturn.

##### `public function pushStatementSkipValues(string ...$values) : self`

*This function adds a value to the skip_statement array.*

*The skip_statement array is used to skip certain values in the statement.*

 * **Returns:** The object itself.

##### `public function getStatementSkipValues(string ...$values) : array`

*This function returns an array of values that should be skipped when executing a statement.*

The above function is used to return an array of values that should be skipped when executing a statement

 * **Returns:** An array of values that should be skipped.

##### `public function setPointer(string $name, $value = null, Statement $statement = null) : string`

This function is used to set a pointer to a value

 * **Parameters:**
   * `string` — The name of the pointer.
   * `value` — value to be bound to the parameter.
   * `Statement` — The statement to bind the value to.

     <p>
 * **Returns:** The name of the pointer.

##### `public function getPointer(string $name) : string`

*Get a pointer to a variable in the PHP script.*

The function takes a string as an argument and returns a string. The string is the name of a variable in the PHP script. If the variable exists in the PHP script, the function returns the pointer to the variable. If the variable does not exist in the PHP script, the function creates the variable and returns the pointer to the variable

 * **Parameters:** `string` — The name of the pointer.
 * **Returns:** The pointer to the value of the variable.
 

<br>

#### ***Class ArangoDB\operations\common\Choose usable methods***

##### `public function __construct(Initiator $core, ...$arguments)`

The constructor for the PHP class is the same as the constructor for the C# class

 * **Parameters:** `Initiator` — The core object that is used to access the database.

##### `public function useWith(bool $with = true) : self`

*This function is used to set the `with` property of the `QueryBuilder` class.*

The `with` property is used to determine whether or not to use the `WITH` clause in the query

 * **Parameters:** `bool` — Whether or not to use the with keyword.

 * **Returns:** The object itself.

##### `public function getWithCollections() : array`

Returns an array of the collections that the current user has access to

 * **Returns:** An array of the collections that are being included in the query.

##### `public function getWithCollectionsParsed() : array`

This function returns an array of all the collections in the database

 * **Returns:** An array of strings.

##### `public function pushWithCollection(string ...$with_collections) : self`

Add a collection to the list of collections to be included in the query

 * **Returns:** The object itself.

##### `public function getLimit() : Limit`

Get the limit for the query

 * **Returns:** The limit object.

##### `public function run() :? array`

This function executes the SQL statement and returns the results

 * **Returns:** The result of the executed Statement.

##### `public function getStatement() : Statement`

This function creates a query for the traversal of the graph

 * **Returns:** The query is being returned.
 
<br>

#### ***Class ArangoDB\operations\common\Handling usable methods***

##### `public function setActionOnlyEdges(bool $value = true) : self`

* Set the action to only use edges

 * **Parameters:** `bool` — The value to set the parameter to.
 * **Returns:** The object itself.

##### `public function getActionOnlyEdges() : bool`

Returns a boolean value indicating whether or not the action is an edge action

 * **Returns:** The getActionOnlyEdges() method returns a boolean value.

##### `public function setActionPreventEmptyDocument(bool $value = true) : self`

If the document is empty, prevent the action from running

 * **Parameters:** `bool` — The value to set the parameter to.
 * **Returns:** The object itself.

##### `public function getActionPreventEmptyDocument() : bool`

It returns a boolean value.

 * **Returns:** The value of the null property.

##### `public function setActionPreventLoop(bool $value = true) : self`

Set the value of the `loop` property

 * **Parameters:** `bool` — The value to set the property to.
  * **Returns:** The object itself.

##### `public function getActionPreventLoop() : bool`

Returns a boolean value indicating whether the current action is a loop

 * **Returns:** The value of the loop property.

##### `public function pushEntitySkips(Entity ...$entities) : self`

*This function adds one or more entities to the list of entities to skip when the `skipEntity` function is called.*

 * **Returns:** The object itself.

##### `public function getEntitySkips() : array`

Returns an array of entity names that should be skipped when processing the data

 * **Returns:** An array of strings.

##### `public function setEntityEnableReturns(Entity ...$entities) : self`

This function sets the entities that will be returned by the `getEntity` method

 * **Returns:** The setEntityEnableReturns method returns the current instance of the class.

##### `public function getEntityEnableReturns() : array`

The `getEntityEnableReturns()` function returns an array of the entity types that are enabled for return

 * **Returns:** The enable_entity_return property is an array of the entity names that are enabled.

##### `public function run() :? array`

This function commits the transaction

 * **Returns:** The commit.

##### `public function pushTransactionsPreliminary(Transaction ...$transactions) : self`

*This function adds a transaction to the list of transactions that will be pushed to the database.*

The function takes in a variable number of arguments, which are all of the type `Transaction`.

 * **Returns:** The object itself.

##### `public function pushTransactionsFinal(Transaction ...$transactions) : self`

*This function adds a Transaction object to the end of the array of Transaction objects.*

The function is a bit more complicated than the previous ones, but it's still pretty simple.

The function takes in an arbitrary number of Transaction objects, and adds them to the end of the array of Transaction objects.

The function returns the current instance of the class, so that it can be chained with other methods.

 * **Returns:** The object itself.

##### `public function getTransaction() : Transaction`

This function is responsible for executing the actions of the script

 * **Returns:** The return value is a Transaction object.

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details