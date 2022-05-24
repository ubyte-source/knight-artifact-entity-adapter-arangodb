<?PHP

namespace ArangoDB\operations\common\base;

use Entity\Map as Entity;

/* A document is a collection of values and edges */

class Document
{
    protected $entity;          // Entity
    protected $traversal = [];  // (array) string
    protected $values = [];     // (array) field name values

    /**
     * Clone the entity and clone the hash entity
     */
    
    public function __clone()
    {
        $clone = clone $this->getEntity();
        $clone->cloneHashEntity($this->getEntity());
        $this->setEntity($clone);
    }

    /**
     * The constructor takes an Entity object and sets the values of the properties of the current
     * object to the values of the properties of the Entity object
     * 
     * @param Entity entity The Entity object that is being filtered.
     */
    
    public function __construct(Entity $entity)
    {
        $this->setEntity($entity);
        $entity_filtered = $entity->getAllFieldsValues(true, false);
        foreach ($entity_filtered as $name => $value) $this->setValue($name, $value);
    }

    /**
     * * Set a value in the values array
     * 
     * @param string name The name of the parameter.
     * @param value The value to be set.
     * 
     * @return The object itself.
     */
    
    public function setValue(string $name, $value) : self
    {
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * Remove the specified fields from the values array
     * 
     * @return The object itself.
     */
    
    public function unsetFields(string ...$fields) : self
    {
        foreach ($fields as $name)
            if (array_key_exists($name, $this->values))
                unset($this->values[$name]);

        return $this;
    }

    /* It returns the values of the document. */

    public function getValues() : array
    {
        return $this->values;
    }

    /**
     * Returns the entity that this component is attached to
     * 
     * @return The entity that was passed in.
     */
    
    public function getEntity() : Entity
    {
        return $this->entity;
    }

    /**
     * *This function sets the traversal of the query.*
     * 
     * The traversal is the edges that will be traversed in the query
     * 
     * @return The object itself.
     */
    
    public function setTraversal(string ...$edges) : self
    {
        $this->traversal = $edges;
        return $this;
    }

    /**
     * Return the traversal of the tree
     * 
     * @return An array of strings.
     */
    
    public function getTraversal() : array
    {
        return $this->traversal;
    }

    /**
     * The setEntity function sets the entity property of the class to the entity parameter
     * 
     * @param Entity entity The entity that is being validated.
     * 
     * @return The object itself.
     */
    
    protected function setEntity(Entity $entity) : self
    {
        $this->entity = $entity;
        return $this;
    }
}
