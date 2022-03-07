<?PHP

namespace ArangoDB\operations\features;

use ArangoDB\Statement;
use ArangoDB\operations\common\base\Document;

/* The `Metadata` trait is used to add metadata to documents. */

trait Metadata
{
    protected $metadata_regex; // (string)

    /**
     * Add metadata to a document
     * 
     * @param Statement statement The statement to add the metadata to.
     * @param Document document The document to add metadata to.
     * 
     * @return The `addDocumentMetadata` method returns a clone of the document with the metadata
     * added.
     */
    
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

    /**
     * * Sets the metadata regex for the document
     * 
     * @param string metadata_regex A regular expression that will be used to extract the metadata from
     * the document.
     * 
     * @return The setDocumentMetadataRegex method returns the object it is called on.
     */
    
    protected function setDocumentMetadataRegex(string $metadata_regex)
    {
        $this->metadata_regex = $metadata_regex;
        return $this;
    }

    /**
     * Returns the regular expression used to parse the metadata from the document
     * 
     * @return The metadata_regex property is being returned.
     */
    
    protected function getDocumentMetadataRegex() :? string
    {
        return $this->metadata_regex;
    }
}