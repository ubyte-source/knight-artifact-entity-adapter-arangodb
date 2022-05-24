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

#### ***Class ArangoDB\operations\common\handling\Modifier usable methods***

##### `public function pushStatementsPreliminary(Statement ...$statements) : self`

*This function pushes a statement or statements onto the array of statements that will be executed.*

The function is called `pushStatementsPreliminary` and it takes an arbitrary number of arguments. The arguments are the statements that will be pushed onto the array of statements that will be executed

 * **Returns:** `Nothing.` — 

##### `public function getStatementsPreliminary() : array`

Returns the statements that were executed during the current transaction

 * **Returns:** An array of statements.

##### `public function pushStatementsFinal(Statement ...$statements) : self`

Add statements to the final array

 * **Returns:** The object itself.

##### `public function getStatementsFinal() : array`

Returns the final array of statements

 * **Returns:** An array of statements.

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
