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

#### ***Class ArangoDB\operations\common\choose\strict\Condition usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** `Nothing.` — 

##### `public function setDeterministic(bool $deterministic = true) : self`

* Sets the deterministic property of the function

 * **Parameters:** `bool` — If true, the random number generator will be seeded with a fixed
  value. This is useful for reproducing results.
 * **Returns:** The object itself.

##### `public function getDeterministic() : bool`

Returns the value of the `deterministic` property

 * **Returns:** The value of the `deterministic` property.

##### `public function addStatement(Statement $statement) : self`

Add a statement to the list of statements

 * **Parameters:** `Statement` — The statement to add to the list of statements.
 
 * **Returns:** The object itself.

##### `public function getStatements() : array`

Returns an array of all the statements in the query

 * **Returns:** An array of statements.

##### `public function addMatches(int ...$number) : self`

Add a number of matches to the array of matches

 * **Returns:** The object itself.

##### `public function getMatches() : array`

Returns the number of matches

 * **Returns:** An array of integers.

##### `public function addHops(Hop ...$hops) : self`

Add a Hop to the Recipe

 * **Returns:** The object itself.

##### `public function getHops() : array`

Get the hops of the beer

 * **Returns:** An array of Hop objects.

<br>

#### ***Class ArangoDB\operations\common\choose\strict\Hop usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** `Nothing.` — 

##### `public function __construct(Document $document)`

The constructor takes a Document object as a parameter and sets it as the document property

 * **Parameters:** `Document` — The document to be parsed.

##### `public function getDocument() : Document`

Returns the document that the current node is in

 * **Returns:** The document object.

##### `public function getHop() : int`

Get the number of hops from the current node to the root node

 * **Returns:** The number of hops from the root node to the current node.

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details