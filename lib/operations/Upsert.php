<?PHP

namespace ArangoDB\operations;

use Knight\armor\CustomException;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\Document;
use ArangoDB\operations\features\Metadata;
use ArangoDB\operations\features\Uniqueness;

class Upsert extends Handling
{
    use Metadata, Uniqueness;

    protected $remover = []; // (array)
    protected $replace;      // (bool)

    public function setReplace(bool $replace) : self
    {
        $this->replace = $replace;
        return $this;
    }

    public function getReplace() :? bool
    {
        return $this->replace;
    }

    protected function action(Transaction $transaction, Statement $statement, Document $document) : void
    {
        $this->setDocumentMetadataRegex('_$0_at');

        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        $document_uniqueness = static::getUniquenessMatch($document);
        if (null === $document_uniqueness) throw new CustomException('developer/unique/' . $collection_name);

        $document_uniqueness = $statement->bindDocument(true, $document_uniqueness);
        if (null === $document_uniqueness) throw new CustomException('developer/unique/' . $collection_name . '/empty');

        $statement->setType($document);
        $statement->append('UPSERT');
        $statement->append($document_uniqueness);

        $document_metadata = $this->addDocumentMetadata($statement, $document, 'created', 'updated');

        $deprecated = $document->getEntity()->getAllFieldsKeys();
        $deprecated = array_diff($deprecated, $document->getEntity()->getAllFieldsRequiredName());
        if (false === empty($deprecated)) $document_metadata->unsetFields(...$deprecated);

        $statement->append('INSERT');
        $statement->append('FIRST' . chr(40), false);
        $statement->append('LET x =');
        $statement->append($statement->bindDocument(false, $document_metadata));
        $statement->append('RETURN MERGE' . chr(40), false);
        $statement->append('FOR i IN ATTRIBUTES(x)');
        $statement->append('FILTER !IS_NULL(x[i])');
        $statement->append('RETURN {[i]: x[i]}', false);
        $statement->append(chr(41), false);
        $statement->append(chr(41));

        $created = $this->getDocumentMetadataRegex();
        $created = str_replace('$0', 'created', $created);
        $document_created = 'OLD' . chr(46) . $created;
        $statement->pushSkipValues($document_created);

        $document_metadata = $this->addDocumentMetadata($statement, $document, 'created', 'updated');
        $document_metadata->setValue($created, $document_created);

        $mode = $this->getReplace() === true;
        $mode = $mode ? 'REPLACE' : 'UPDATE';

        $statement->append($mode);
        $statement->append('FIRST' . chr(40), false);
        $statement->append('LET x =');
        $statement->append($statement->bindDocument(false, $document_metadata));
        $statement->append('RETURN MERGE' . chr(40), false);
        $statement->append('FOR i IN ATTRIBUTES(x)');
        $statement->append('FILTER !IS_NULL(x[i])');
        $statement->append('RETURN {[i]: x[i]}', false);
        $statement->append(chr(41), false);
        $statement->append(chr(41));
        $statement->append('IN');
        $statement->append($collection_name);
        $statement->append('OPTIONS {exclusive: true, waitForSync: true}');
        $statement->append('RETURN {type: "' . $collection_type . '", collection: "' . $collection_name . '", document: NEW, replaced: OLD ? OLD : null}', false);

        $transaction->pushStatements($statement);
    }
}