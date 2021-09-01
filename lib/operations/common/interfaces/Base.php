<?PHP

namespace ArangoDB\operations\common\interfaces;

use ArangoDB\Initiator;

interface Base
{
    public function __construct(Initiator $core, ...$arguments);

    public function run() :? array;
}