<?PHP

namespace ArangoDB\operations;

use Knight\armor\CustomException;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\entity\common\Arango;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\features\Metadata;
use ArangoDB\operations\features\Uniqueness;
use ArangoDB\operations\features\Match;

/* This class is used to update a document in a collection */

class Update extends Handling
{
    use Metadata, Uniqueness;

    const SEARCH = 'search';
    const RESPONSE = '{type: "%s", collection: "%s", document: %s, replaced: %s}';

    protected $remover = []; // (array)
    protected $replace;      // (bool)

    /**
     * * Set the replace parameter to true or false
     * 
     * @param bool replace If true, the existing table will be dropped and recreated.
     * 
     * @return The object itself.
     */
    
    public function setReplace(bool $replace) : self
    {
        $this->replace = $replace;
        return $this;
    }

    /**
     * Returns the value of the replace property
     * 
     * @return The replace property.
     */
    
    public function getReplace() :? bool
    {
        return $this->replace;
    }

    /**
     * * For each document in the collection, if the document does not exist in the collection, then
     * create the document.
     * * If the document exists in the collection, then update the document.
     * * Return the new document and the old document
     * 
     * @param Transaction transaction The transaction object.
     * @param Statement statement The statement that will be executed.
     * @param Document document The document that is being updated.
     */
    
    protected function action(Transaction $transaction, Statement $statement, Document $document) : void
    {
        $this->setDocumentMetadataRegex('_$0_at');

        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        $statement->setType($document);
        $statement->append('FOR');
        $statement->append(static::SEARCH);
        $statement->append('IN');
        $statement->append($collection_name);

        $document_metadata = $this->addDocumentMetadata($statement, $document, 'created', 'updated');
        $document_metadata->unsetFields(Arango::KEY);

        $uniqueness = static::getUniquenessMatch($document_metadata);
        if (null === $uniqueness) throw new CustomException('developer/unique/' . $collection_name);
        if ($matches = $this->matches(static::SEARCH, $statement, $uniqueness)) {
            $matches_conditions = implode(chr(32) . 'AND' . chr(32), $matches);
            $statement->append('FILTER');
            $statement->append($matches_conditions);
        }

        $mode = $this->getReplace() === true ? 'REPLACE' : 'UPDATE';
        $statement->append($mode);
        $statement->append(static::SEARCH);

        $created = $this->getDocumentMetadataRegex();
        $created = str_replace('$0', 'created', $created);
        $document_metadata_value = static::SEARCH . chr(46) . $created;
        $document_metadata->setValue($created, $document_metadata_value);
        $statement->pushSkipValues($document_metadata_value);

        $deprecated = $document->getEntity()->getAllFieldsKeys();
        $deprecated = array_diff($deprecated, $document->getEntity()->getAllFieldsRequiredName());
        if (false === empty($deprecated)) $document_metadata->unsetFields(...$deprecated);

        $statement->append('WITH');
        $statement->append('FIRST(', false);
        $statement->append('LET x =');
        $statement->append($statement->bindDocument(false, $document_metadata));
        $statement->append('RETURN MERGE(', false);
        $statement->append('FOR i IN ATTRIBUTES(x)');
        $statement->append('FILTER !IS_NULL(x[i])');
        $statement->append('RETURN {[i]: x[i]}', false);
        $statement->append(')', false);
        $statement->append(')');
        $statement->append('IN');
        $statement->append($collection_name);
        $statement->append('OPTIONS {exclusive: true, waitForSync: true}');

        $this->shouldReturn($statement, function (Statement $statement) use ($collection_type, $collection_name) {
            $statement->append('RETURN');
            $statement_return = sprintf(static::RESPONSE, $collection_type, $collection_name, Handling::RNEW, Handling::ROLD);
            $statement->append($statement_return, false);
        });

        $transaction->pushStatements($statement);
    }
}