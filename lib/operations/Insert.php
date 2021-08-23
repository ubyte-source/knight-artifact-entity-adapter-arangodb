<?PHP

namespace ArangoDB\operations;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\Document;
use ArangoDB\operations\features\Metadata;

class Insert extends Handling
{
    use Metadata;

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
        $statement->append('RETURN {type: "' . $collection_type . '", collection: "' . $collection_name . '", document: NEW}', false);

        $transaction->pushStatements($statement);
    }
}