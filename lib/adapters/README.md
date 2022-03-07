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

#### ***Class ArangoDB\adapters\Edge usable methods***

##### `public function __clone()`

Clone the object and set the new object's `To` property to the same value as the original object's `To` property

##### `public function __construct(ADBEdge $edge)`

The constructor takes an ADBEdge object as a parameter and stores it in the target property

 * **Parameters:** `ADBEdge` — The edge that is being created.

##### `public function setFrom(?ADBEdge $edge, Vertex $vertex) : void`

* Sets the `from` vertex of the edge

 * **Parameters:**
   * `edge` — edge that is being set as the from edge.
   * `Vertex` — The vertex that is the end of the edge.

##### `public function getFrom() : Vertex`

Get the vertex that this edge is coming from

 * **Returns:** The from vertex.

##### `public function setTo(?ADBEdge $edge, Vertex $vertex) : void`

Set the edge's destination vertex

 * **Parameters:**
   * `edge` — edge that is being set to a new value.
   * `Vertex` — The vertex that is the end of the edge.

##### `public function getTo() : Vertex`

Returns the vertex that this edge is pointing to

 * **Returns:** The to property of the Edge object.

##### `public function vertex(ADBEdge $edge, Vertex $vertex = null, bool $interconnect = false) : Vertex`

* Given an edge and a vertex, return the vertex at the other end of the edge

 * **Parameters:**
   * `ADBEdge` — The edge that is being traversed.
   * `Vertex` — The vertex to connect to.
   * `bool` — If true, the vertex will be connected to the previous vertex.

     <p>
 * **Returns:** `A` — new instance of the `Vertex` class.

##### `public function branch(ADBEdge $edge) : ADBEdge`

Create a new node that is a copy of the current node, but with a different edge

 * **Parameters:** `ADBEdge` — The edge to branch from.
 
<br>

#### ***Class ArangoDB\adapters\Vertex usable methods***

##### `public function __construct()`

The constructor sets the container to null and creates a new Container object

##### `public function __clone()`

Clone the object and all of its properties

 * **Returns:** `Nothing.` — 

##### `public function setContainer(?ADBVertex $vertex, Container $container) : self`

* Sets the container for the vertex

 * **Parameters:**
   * `vertex` — vertex that is being added to the container.
   * `Container` — The container that the vertex is in.

     <p>
 * **Returns:** The object itself.

##### `public function getContainer() : Container`

Returns the container that was used to create this object

 * **Returns:** The Container object.

##### `public function useEdge(ADBVertex $vertex, string $edgename, Edge ...$edges) : Edge`

*This function is used to create a new edge instance from an existing edge instance.*

The function takes three parameters:

* `$vertex` - The vertex instance that the edge will be attached to. * ` $edgename` - The name of the edge class to be instantiated. * `...$edges` - An array of edge instances that will be cloned and attached to the new edge instance

 * **Parameters:**
   * `ADBVertex` — The vertex that the edge is being added to.
   * `string` — The name of the edge.

     <p>
 * **Returns:** The last edge in the chain.

##### `public function getAllUsableEdgesName(ADBVertex $vertex, bool $shortname = true) : array`

Get all the edge names that are usable by the given vertex

 * **Parameters:**
   * `ADBVertex` — The vertex to get all usable edges from.
   * `bool` — If true, the collection name will be the short name.

     <p>
 * **Returns:** An array of edge names.
 
## Built With

* [PHP](https://www.php.net/) - PHP

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details