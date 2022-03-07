<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\operations\common\base\Document;

/* The `getUniquenessMatch` method is a helper method that is used to find a document that has the same
values as the document passed in as a parameter. */

trait Uniqueness
{
    /**
     * If the document has a unique constraint, then we can use it to find a document that already
     * exists in the database
     * 
     * @param Document document The document to check for uniqueness.
     */
    
    protected static function getUniquenessMatch(Document $document) :? Document
    {
        $clone = clone $document;
        $clone_keys = $clone->getValues();
        $clone_keys = array_keys($clone_keys);
        $clone_unique_fields = $clone->getEntity()->getAllFieldsUniqueGroups();
        foreach ($clone_unique_fields as $unique_group) {
            $clone_keys_unique_fields_group = array_intersect($clone_keys, $unique_group);
            if (count($unique_group) !== count($clone_keys_unique_fields_group)) continue;

            $clone_keys_unique_fields_group = array_diff($clone_keys, $clone_keys_unique_fields_group);
            $clone->unsetFields(...$clone_keys_unique_fields_group);
            return $clone;
        }
        return null;
    }
}