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

#### ***Class ArangoDB\operations\Select usable methods***

##### `public function setMaxHop(int $hop) : self`

* Set the maximum number of hops for the packet

 * **Parameters:** `int` — The number of hops to take.

     <p>
 * **Returns:** The object itself.

##### `public function getMaxHop() :? int`

"Get the maximum number of hops."

The function name is getMaxHop

 * **Returns:** The max_hop property of the object.

<br>

#### ***Class ArangoDB\operations\Update usable methods***

##### `public function setReplace(bool $replace) : self`

* Set the replace parameter to true or false

 * **Parameters:** `bool` — If true, the existing table will be dropped and recreated.

     <p>
 * **Returns:** The object itself.

##### `public function getReplace() :? bool`

Returns the value of the replace property

 * **Returns:** The replace property.
 
<br>

#### ***Class ArangoDB\operations\Upsert usable methods***

##### `public function setReplace(bool $replace) : self`

* Set the replace parameter to true or false

 * **Parameters:** `bool` — If true, the existing table will be dropped and recreated.

     <p>
 * **Returns:** The object itself.

##### `public function getReplace() :? bool`

Returns the value of the replace property

 * **Returns:** The replace property.

## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details