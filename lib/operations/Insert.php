<?PHP

namespace ArangoDB\operations;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\features\Metadata;

/* Inserts a document into a collection */

class Insert extends Handling
{
    use Metadata;

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
        $this->setDocumentMetadataRegex('_$0_at');

        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        $document_metadata = $this->addDocumentMetadata($statement, $document, 'created', 'updated');
        $document_metadata_bind = $statement->bindDocument(false, $document_metadata);

        $statement->setType($document_metadata);
        $statement->append('INSERT');
        $statement->append($document_metadata_bind);
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
