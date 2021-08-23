<?php

namespace ArangoDB\entity;

use Entity\Field;
use Entity\Validation;

use ArangoDB\entity\common\Arango;

abstract class Vertex extends Arango
{
    const TYPE = 'vertex';
    const MANAGEMENT = [
        'author',
        'owner'
    ];

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