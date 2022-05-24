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

#### ***Class ArangoDB\operations\common\base\Document usable methods***

##### `public function __clone()`

Clone the entity and clone the hash entity

##### `public function __construct(Entity $entity)`

The constructor takes an Entity object and sets the values of the properties of the current object to the values of the properties of the Entity object

 * **Parameters:** `Entity` — The Entity object that is being filtered.

##### `public function setValue(string $name, $value) : self`

* Set a value in the values array

 * **Parameters:**
   * `string` — The name of the parameter.
   * `value` — value to be set.

 * **Returns:** The object itself.

##### `public function unsetFields(string ...$fields) : self`

Remove the specified fields from the values array

 * **Returns:** The object itself.

##### `public function getEntity() : Entity`

Returns the entity that this component is attached to

 * **Returns:** The entity that was passed in.

##### `public function setTraversal(string ...$edges) : self`

*This function sets the traversal of the query.*
The traversal is the edges that will be traversed in the query

 * **Returns:** The object itself.

##### `public function getTraversal() : array`

Return the traversal of the tree

 * **Returns:** An array of strings.

<br>

#### ***Class ArangoDB\operations\common\base\SReturn usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** `Nothing.` — 

##### `public function __construct()`

The constructor for the PHP class

##### `public function setFromStatement(Statement $statement, ...$binds) : self`

This function sets the query of the current query builder to the query of the given statement

 * **Parameters:** `Statement` — The statement to use.
 * **Returns:** The object itself.

##### `public function setPlain(string $statement_query, ...$binds) : self`

* The function takes a query and binds to it. * It then replaces the bind markers with the actual values. * It then appends the query to the statement.

The function is used in the following way:

 * **Parameters:** `string` — The query to be executed.
 * **Returns:** The `setPlain` method returns the `self` reference.

##### `public function getStatement() :? Statement`

Returns the statement that was used to create this result set

 * **Returns:** The statement that was executed.

##### `public function checkUsed(string $string) : bool`

Check if a string is used in the query

 * **Parameters:** `string` — The string to check for in the query.
 * **Returns:** `A` — boolean value.

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
