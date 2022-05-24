<?php

namespace ArangoDB\entity;

use Entity\Field;
use Entity\Validation;

use ArangoDB\entity\common\Arango;

/* Vertex is an abstract class that extends Arango and defines a set of fields that are required for
all vertices */

abstract class Vertex extends Arango
{
    const TYPE = 'vertex';
    const AUTHOR = 'author';
    const OWNER = 'owner';
    const MANAGEMENT = [
        Vertex::AUTHOR,
        Vertex::OWNER
    ];

    /**
     * * Add a field for each of the management fields.
     * * Set the pattern for each field to be a number.
     * * Set the field to be protected.
     * * Set the field to be required
     */
    
    protected function before() : void
    {
        parent::before();

        foreach (static::MANAGEMENT as $name) {
            $management = $this->addField($name);
            $management_pattern = Validation::factory('Regex');
            $management_pattern->setRegex('/^\d+$/');
            $management->setPatterns($management_pattern);
            $management->setProtected();
            $management->setRequired();
        }
    }
}
