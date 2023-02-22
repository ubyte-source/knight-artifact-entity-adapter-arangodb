<?PHP

namespace ArangoDB\operations;

use Knight\armor\CustomException;

use ArangoDB\Statement;
use ArangoDB\Transaction;
use ArangoDB\operations\common\Handling;
use ArangoDB\operations\common\base\Document;
use ArangoDB\operations\features\Uniqueness;

/* This class is used to upsert documents into a collection */

class Upsert extends Handling
{
    use Uniqueness;

    const RESPONSE = '{type: "%s", collection: "%s", document: %s, replaced: %s ? %s : null}';

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
     * It creates a new document in the collection.
     * 
     * @param Transaction transaction The transaction object.
     * @param Statement statement The statement to be executed.
     * @param Document document The document to be inserted.
     */
    
    protected function action(Transaction $transaction, Statement $statement, Document $document) : void
    {
        $collection_name = $document->getEntity()->getCollectionName();
        $collection_type = $document->getEntity()->getType();

        $document_uniqueness = static::getUniquenessMatch($document);
        if (null === $document_uniqueness) throw new CustomException('developer/unique/' . $collection_name);

        $document_uniqueness = $statement->bindDocument(true, $document_uniqueness);
        if (null === $document_uniqueness) throw new CustomException('developer/unique/' . $collection_name . '/empty');

        $statement->setType($document);
        $statement->append('UPSERT');
        $statement->append($document_uniqueness);

        $document_metadata = clone $document;

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

        $mode = $this->getReplace() === true;
        $mode = $mode ? 'REPLACE' : 'UPDATE';

        $statement->append($mode);
        $statement->append('FIRST' . chr(40), false);
        $statement->append('LET x =');
        $statement->append($statement->bindDocument(false, $document));
        $statement->append('RETURN MERGE' . chr(40), false);
        $statement->append('FOR i IN ATTRIBUTES(x)');
        $statement->append('FILTER !IS_NULL(x[i])');
        $statement->append('RETURN {[i]: x[i]}', false);
        $statement->append(chr(41), false);
        $statement->append(chr(41));
        $statement->append('IN');
        $statement->append($collection_name);
        $statement->append('OPTIONS {exclusive: true, waitForSync: true}');

        $this->shouldReturn($statement, function (Statement $statement) use ($collection_type, $collection_name) {
            $statement->append('RETURN');
            $statement_return = sprintf(static::RESPONSE, $collection_type, $collection_name, Handling::RNEW, Handling::ROLD, Handling::ROLD);
            $statement->append($statement_return, false);
        });

        $transaction->pushStatements($statement);
    }
}
