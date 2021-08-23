<?PHP

namespace ArangoDB\operations\common;

use Entity\Map as Entity;

class Document
{
    protected $entity;          // Entity
    protected $traversal = [];  // (array) string
    protected $values = [];     // (array) field name values

    public function __clone()
    {
        $clone = clone $this->getEntity();
        $clone->cloneHashEntity($this->getEntity());
        $this->setEntity($clone);
    }

    public function __construct(Entity $entity)
    {
        $this->setEntity($entity);
        $entity_filtered = $entity->getAllFieldsValues(true, false);
        foreach ($entity_filtered as $name => $value) $this->setValue($name, $value);
    }

    public function setValue(string $name, $value) : self
    {
        $this->values[$name] = $value;
        return $this;
    }

    public function unsetFields(string ...$fields) : self
    {
        foreach ($fields as $name)
            if (array_key_exists($name, $this->values))
                unset($this->values[$name]);

        return $this;
    }

    public function getValues() : array
    {
        return $this->values;
    }

    public function getEntity() : Entity
    {
        return $this->entity;
    }

    public function setTraversal(string ...$edges) : self
    {
        $this->traversal = $edges;
        return $this;
    }

    public function getTraversal() : array
    {
        return $this->traversal;
    }

    protected function setEntity(Entity $entity) : self
    {
        $this->entity = $entity;
        return $this;
    }
}