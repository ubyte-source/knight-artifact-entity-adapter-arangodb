<?PHP

namespace ArangoDB\operations\common\choose\strict;

use ArangoDB\entity\Edge;
use ArangoDB\operations\common\base\Document;

class Hop
{
    protected $document; // Document

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

    public function __construct(Document $document)
    {
        $this->setDocument($document);
    }

    public function getDocument() : Document
    {
        return $this->document;
    }

    public function getHop() : int
    {
        $decrement = $this->getDocument()->getEntity()->getType() === Edge::TYPE ? 1 : 0;
        $traversal = $this->getDocument()->getTraversal();
        $traversal = count($traversal) - $decrement;
        return $traversal ?: 0;
    }

    protected function setDocument(Document $document) : void
    {
        $this->document = $document;
    }
}