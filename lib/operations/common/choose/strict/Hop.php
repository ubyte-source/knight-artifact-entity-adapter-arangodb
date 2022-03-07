<?PHP

namespace ArangoDB\operations\common\choose\strict;

use ArangoDB\entity\Edge;
use ArangoDB\operations\common\base\Document;

/* Hop is a class that represents a single hop in a traversal */

class Hop
{
    protected $document; // Document

    /**
     * Clone the object and all of its properties
     * 
     * @return Nothing.
     */
    
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

    /**
     * The constructor takes a Document object as a parameter and sets it as the document property
     * 
     * @param Document document The document to be parsed.
     */
    
    public function __construct(Document $document)
    {
        $this->setDocument($document);
    }

    /**
     * Returns the document that the current node is in
     * 
     * @return The document object.
     */
    
    public function getDocument() : Document
    {
        return $this->document;
    }

    /**
     * Get the number of hops from the current node to the root node
     * 
     * @return The number of hops from the root node to the current node.
     */
    
    public function getHop() : int
    {
        $decrement = $this->getDocument()->getEntity()->getType() === Edge::TYPE ? 1 : 0;
        $traversal = $this->getDocument()->getTraversal();
        $traversal = count($traversal) - $decrement;
        return $traversal ?: 0;
    }

    /**
     * The setDocument function sets the document property to the value of the document parameter
     * 
     * @param Document document The document to be parsed.
     */
    
    protected function setDocument(Document $document) : void
    {
        $this->document = $document;
    }
}