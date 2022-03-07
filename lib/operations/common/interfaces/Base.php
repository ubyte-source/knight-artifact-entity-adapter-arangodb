<?PHP

namespace ArangoDB\operations\common\interfaces;

use ArangoDB\Initiator;

/* A contract for all the classes that implement the `Base` interface. */

interface Base
{
    /* This is the constructor of the class. It is used to initialize the class. */

    public function __construct(Initiator $core, ...$arguments);

    /* Returning a nullable array. */
    
    public function run() :? array;
}