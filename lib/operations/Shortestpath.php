<?PHP

namespace ArangoDB\operations;

use Knight\armor\CustomException;

use ArangoDB\Parser;
use ArangoDB\Statement;
use ArangoDB\operations\common\Choose;

class Shortestpath extends Choose
{
    const SHORTEST = 'shortest';

    protected function main(Parser $parser, Statement $statement) : void
    {
        $collections = $parser->getTargetsVertexName();
        if (1 !== count($collections)) throw new CustomException('developer/multiple/target');

        $statement->append('FOR');
        $statement->append($this->getPointer(static::SHORTEST));
        $statement->append('IN');
        $statement->append(reset($collections));

        $statement->append('FOR');
        $statement->append($this->getPointer(Choose::VERTEX) . chr(44));
        $statement->append($this->getPointer(Choose::EDGE));
        $statement->append('IN');
        $statement->append('ANY SHORTEST_PATH');
        $statement->append($this->getPointer('start'));
        $statement->append('TO');
        $statement->append($this->getPointer(static::SHORTEST));
    }

    protected function manipulate(Parser $parser, Statement $statement) : void
    {
        $statement_traversal = $this->getPointer(Choose::TRAVERSAL);
        if ($this->getReturn()->checkUsed($statement_traversal) || $this->shouldUseStrict($parser)) {
            $statement_edge = $this->getPointer(Choose::EDGE);
            $statement_vertex = $this->getPointer(Choose::VERTEX);

            $statement->append('COLLECT f = ' . $this->getPointer(static::SHORTEST) . ' INTO a KEEP ' . $statement_edge . chr(44) . chr(32) . $statement_vertex);
            $statement->append('LET ' . $statement_traversal . ' =');
            $statement->append('{"vertices": a[*].' . $statement_vertex . chr(44) . chr(32) . '"edges": SHIFT(a[*].' . $statement_edge . ')}');
            $statement->append('LET ' . $statement_edge . ' = LAST(' . $statement_traversal . '.edges)');
            $statement->append('LET ' . $statement_vertex . ' = ' . 'f');
        }
    }
}