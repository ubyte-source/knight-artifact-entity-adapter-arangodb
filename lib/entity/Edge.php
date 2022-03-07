<?php

namespace ArangoDB\entity;

use Knight\armor\CustomException;

use Entity\Validation;

use ArangoDB\entity\common\Arango;

/* An edge is a connection between two vertices */

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

    /**
     * * Add a field for each of the DISTINCTIVE properties.
     * * Set the pattern for each field to be a ShowString.
     * * Set the uniqueness constraint for each field to be the type of the entity.
     * * Set the field to be protected.
     * * Set the field to be required
     */
    
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

    /**
     * Get the name of the class
     * 
     * @return The short name of the class.
     */
    
    public static function getName() : string
    {
        $static = new static();
        return $static->getReflection()->getShortName();
    }

    /**
     * It returns the direction of the arrow.
     * 
     * @return The direction of the sort.
     */
    
    public function getDirection() : string
    {
        return static::DIRECTION;
    }

    /**
     * Returns the target of the current request
     * 
     * @return The target of the migration.
     */
    
    public function getTarget() : string
    {
        return static::TARGET;
    }

    /**
     * * Set the force direction of the object
     * 
     * @param string direction The direction of the force.
     * 
     * @return The object itself.
     */
    
    public function setForceDirection(string $direction) : self
    {
        if (!in_array($direction, static::ADMITTED)) throw new CustomException('developer/direction');
        $this->direction = $direction;
        return $this;
    }

    /**
     * Returns the force direction if it exists, otherwise returns null
     * 
     * @return The direction value.
     */
    
    public function getForceDirection() :? string
    {
        return $this->direction;
    }
}