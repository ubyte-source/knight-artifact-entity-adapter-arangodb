<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\operations\common\Document;

trait Uniqueness
{
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