<?php

namespace ArangoDB\entity;

use Knight\armor\CustomException;

use Entity\Validation;

use ArangoDB\entity\common\Arango;

abstract class Edge extends Arango
{
    const INBOUND = 'inbound';
    const OUTBOUND = 'outbound';
    const ANY = 'any';

    const TYPE = 'edge';
    const ADMITTED = [
        self::INBOUND,
        self::OUTBOUND,
        self::ANY
    ];

    const FROM = '_from';
    const TO = '_to';

    const DISTINCTIVE = [
        Edge::FROM,
        Edge::TO
    ];

    //const DIRECTION = 'inbound|outbound|any';
    //const TARGET = 'target/vertex';

    protected $direction;

    protected function before() : void
    {
        foreach (static::DISTINCTIVE as $name) {
            $from = $this->addField($name);
            $from_pattern = Validation::factory('ShowString');
            $from->setPatterns($from_pattern);
            $from->addUniqueness(static::TYPE);
            $from->setProtected();
            $from->setRequired();
        }

        parent::before();
    }

    public function getDirection() : string
    {
        return static::DIRECTION;
    }

    public function getTarget() : string
    {
        return static::TARGET;
    }

    public function setForceDirection(string $direction) : self
    {
        if (!in_array($direction, static::ADMITTED)) throw new CustomException('developer/direction');
        $this->direction = $direction;
        return $this;
    }

    public function getForceDirection() :? string
    {
        return $this->direction;
    }
}