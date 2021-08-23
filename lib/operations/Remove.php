<?PHP

namespace ArangoDB\operations;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\Document;
use ArangoDB\operations\features\Metadata;

class Remove extends Handling
{
    use Metadata;

    const ITERATION = 'iteration';

    protected function before(Statement $statement, Document $data) : void
    {
        $statement->append('FOR');
        $statement->append($this->getPointer(static::ITERATION));
        $statement->append('IN');
        $statement->append($data->getEntity()->getCollectionName());
    }

    protected function action(Transaction $transaction, Statement $statement, Document $document) : void
    {
        $iterations = $this->getPointer(static::ITERATION);
        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        if ($document_matches = $this->matches($iterations, $statement, $document)) {
            $document_matches_conditions = implode(chr(32) . 'AND' . chr(32), $document_matches);
            $statement->append('FILTER');
            $statement->append($document_matches_conditions);
        }

        $statement->setType($document);
        $statement->append('REMOVE');
        $statement->append($iterations);
        $statement->append('IN');
        $statement->append($collection_name);
        $statement->append('OPTIONS {waitForSync: true}');
        $statement->append('RETURN {type: "' . $collection_type . '", collection: "' . $collection_name . '", document: OLD}', false);

        $transaction->pushStatements($statement);
        $transaction->setEdgeFirst(true);
    }
}