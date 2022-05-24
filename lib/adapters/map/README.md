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

#### ***Class ArangoDB\adapters\map\Bind usable methods***

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** The cloned object.

##### `public function pushEdges(Vertex $vertex, Edge ...$edges) : self`

* The function accepts a vertex and an arbitrary number of edges. * It then checks to make sure the namespace of the vertex and the edge are the same. * It then attaches the adapter to the edge and sets the vertex as the from vertex. * Finally, it pushes the edge onto the edges array.

Now, let's take a look at the code for the `pushVertices` function.

 * **Parameters:** `Vertex` — The vertex to attach the edges to.

     <p>
 * **Returns:** The container itself.

##### `public function removeEdgesByName(string ...$names) : self`

Remove all edges whose reflection's short name is in the given array of names

 * **Returns:** The object itself.

##### `public function getEdgesByName(string ...$names) : array`

Get all the edges that have a reflection with a short name that is in the given list of names

 * **Returns:** An array of edges.

##### `public function getEdges() : array`

Return an array of all the edges in the graph

 * **Returns:** An array of Edge objects.
 
## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
