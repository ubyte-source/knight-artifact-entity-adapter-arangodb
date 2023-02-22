<?PHP

namespace ArangoDB\operations;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\base\Document;

/* Remove documents from a collection */

class Remove extends Handling
{
    const ITERATION = 'iteration';

    /**
     * *This function is called before the loop is executed.*
     * 
     * The `before` function is called before the loop is executed. This is where you can add any
     * additional code that you want to add before the loop is executed
     * 
     * @param Statement statement The statement that will be modified.
     * @param Document data The data to be iterated over.
     */
    
    protected function before(Statement $statement, Document $data) : void
    {
        $statement->append('FOR');
        $statement->append($this->getPointer(static::ITERATION));
        $statement->append('IN');
        $statement->append($data->getEntity()->getCollectionName());
    }

    /**
     * If the document matches the conditions, remove the document from the collection
     * 
     * @param Transaction transaction The transaction object.
     * @param Statement statement The statement that will be executed.
     * @param Document document The document that is being removed.
     */
    
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

        $this->shouldReturn($statement, function (Statement $statement) use ($collection_type, $collection_name) {
            $statement->append('RETURN');
            $statement_return = sprintf(Handling::RESPONSE, $collection_type, $collection_name, Handling::ROLD);
            $statement->append($statement_return, false);
        });

        $transaction->pushStatements($statement);
        $transaction->setEdgeFirst(true);
    }
}
