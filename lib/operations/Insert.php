<?PHP

namespace ArangoDB\operations;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\base\Document;

/* Inserts a document into a collection */

class Insert extends Handling
{
    /**
     * * The action function is called when a document is created.
     * * It sets the document metadata and inserts the document into the collection.
     * * It returns the document metadata
     * 
     * @param Transaction transaction The transaction object.
     * @param Statement statement The statement to be executed.
     * @param Document document The document that is being inserted.
     */

    protected function action(Transaction $transaction, Statement $statement, Document $document) : void
    {
        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        $statement->setType($document);
        $statement->append('INSERT');
        $statement->append($statement->bindDocument(false, $document));
        $statement->append('IN');
        $statement->append($collection_name);
        $statement->append('OPTIONS {exclusive: true, waitForSync: true}');

        $this->shouldReturn($statement, function (Statement $statement) use ($collection_type, $collection_name) {
            $statement->append('RETURN');
            $statement_return = sprintf(Handling::RESPONSE, $collection_type, $collection_name, Handling::RNEW);
            $statement->append($statement_return, false);
        });

        $transaction->pushStatements($statement);
    }
}
