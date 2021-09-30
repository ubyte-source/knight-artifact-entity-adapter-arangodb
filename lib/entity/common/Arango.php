<?php

namespace ArangoDB\entity\common;

use Entity\Map as Entity;
use Entity\Field;
use Entity\Validation;

abstract class Arango extends Entity
{
    //const TYPE = 'edge|vertex|table';

    const ID = '_id';
    const KEY = '_key';
    const REV = '_rev';
    const SEPARATOR = '/';

    public function getType() :? string
    {
        $reflection_constants = $this->getReflection();
        $reflection_constants = $reflection_constants->getConstants();
        return array_key_exists('TYPE', $reflection_constants) ? static::TYPE : null;
    }

    protected function before() : void
    {
        $id = $this->addField(Arango::ID);
        $id_pattern = Validation::factory('ShowString');
        $id->setPatterns($id_pattern);
        $id->addUniqueness('secondary');
        $id->setProtected();
        $id->setRequired();

        $key = $this->addField(Arango::KEY);
        $key_pattern = Validation::factory('Regex');
        $key_pattern->setRegex('/^\w+$/');
        $key->setPatterns($key_pattern);
        $key->addUniqueness(Field::PRIMARY);
        $key->setProtected();
        $key->setRequired();
        $key->setTrigger(function () {
            $id = $this->getCore()->getField(Arango::ID);
            $id->setDefault();
            if ($this->isDefault()) return true;

            $id_value = $this->getCore()->getCollectionName() . Arango::SEPARATOR . $this->getValue();
            $id_safemode = $id->getCore()->getSafeMode();
            $id->getCore()->setSafeMode(false);
            $id->setValue($id_value);
            $id->getCore()->setSafeMode($id_safemode);

            return true;
        });

        $rev = $this->addField(Arango::REV);
        $rev_pattern = Validation::factory('ShowString');
        $rev->setPatterns($rev_pattern);
        $rev->setProtected();

        $created_at = $this->addField('_created_at');
		$created_at_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'U');
		$created_at->setPatterns($created_at_validator);
        $created_at->setProtected();

		$updated_at = $this->addField('_updated_at');
		$updated_at_validator = Validation::factory('DateTime', null, 'd-m-Y H:i:s', 'U');
		$updated_at->setPatterns($updated_at_validator);
        $updated_at->setProtected();
    }
}