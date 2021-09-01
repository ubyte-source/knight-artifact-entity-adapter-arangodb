<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\Statement;
use ArangoDB\operations\common\base\Document;

trait Metadata
{
    protected $metadata_regex; // (string)

    protected function addDocumentMetadata(Statement $statement, Document $document, string ...$metadata) : Document
    {
        $clone = clone $document;
        if (empty($metadata)) return $clone;

        $at = 'CEIL(DATE_NOW() / 1e3)';
        $statement->pushSkipValues($at);
        $metadata_regex = $this->getDocumentMetadataRegex();
        if (null !== $metadata_regex) $metadata = preg_filter('/^.*$/', $metadata_regex, $metadata);

        array_walk($metadata, function ($name) use ($clone, $at) {
            $clone->setValue($name, $at);
        });

        return $clone;
    }

    protected function setDocumentMetadataRegex(string $metadata_regex)
    {
        $this->metadata_regex = $metadata_regex;
        return $this;
    }

    protected function getDocumentMetadataRegex() :? string
    {
        return $this->metadata_regex;
    }
}