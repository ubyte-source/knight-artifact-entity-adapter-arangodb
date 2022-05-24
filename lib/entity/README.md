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

#### ***Class ArangoDB\entity\Edge usable methods***

##### `protected function before() : void`

* Add a field for each of the DISTINCTIVE properties. * Set the pattern for each field to be a ShowString. * Set the uniqueness constraint for each field to be the type of the entity. * Set the field to be protected. * Set the field to be required

##### `public static function getName() : string`

Get the name of the class

 * **Returns:** The short name of the class.

##### `public function getDirection() : string`

It returns the direction of the arrow.

 * **Returns:** The direction of the sort.

##### `public function getTarget() : string`

Returns the target of the current request

 * **Returns:** The target of the migration.

##### `public function setForceDirection(string $direction) : self`

* Set the force direction of the object

 * **Parameters:** `string` — The direction of the force.
 * **Returns:** The object itself.

##### `public function getForceDirection() :? string`

Returns the force direction if it exists, otherwise returns null

 * **Returns:** The direction value.
 
## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
