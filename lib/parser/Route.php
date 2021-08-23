<?PHP

namespace ArangoDB\parser;

use ArangoDB\operations\common\Document;

class Route
{
    protected $documents = []; // (array) Document

    public function __clone()
    {
        $variables = get_object_vars($this);
        $variables = array_keys($variables);
        $variables_glue = [];
        foreach ($variables as $name) array_push($variables_glue, array(&$this->$name));
        array_walk_recursive($variables_glue, function (&$item, $name) {
            if (false === is_object($item)) return;
            $item = clone $item;
        });
    }

    public function pushDocuments(Document ...$pushed) : self
    {
        $documents = $this->getDocuments();
        $documents = array_map(function (Document $document) {
            return $document->getEntity()->getHash();
        }, $documents);

        $unique = array_unique($pushed, SORT_REGULAR);
        $unique = array_filter($unique, function (Document $document) use ($documents) {
            return false === in_array($document->getEntity()->getHash(), $documents);
        });
        $unique = array_values($unique);
        array_push($this->documents, ...$unique);

        return $this;
    }

    public function getDocuments() : array
    {
        return $this->documents;
    }
}