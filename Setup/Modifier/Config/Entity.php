<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Modifier\Config;

use Ess\M2ePro\Setup\Modifier\Config;

class Entity
{
    private $group = NULL;
    private $key = NULL;

    /**
     * @var Config
     */
    protected $configModifier;

    //########################################

    public function __construct(Config $configModifier, $group, $key)
    {
        $this->configModifier = $configModifier;

        $this->group = $group;
        $this->key   = $key;
    }

    //########################################

    public function isExists()
    {
        return $this->configModifier->isExists($this->group, $this->key);
    }

    // ---------------------------------------

    public function getGroup()
    {
        return $this->group;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getValue()
    {
        $row = $this->configModifier->getRow($this->group, $this->key);
        return isset($row['value']) ? $row['value'] : NULL;
    }

    // ---------------------------------------

    public function insert($value)
    {
        $result = $this->configModifier->insert($this->group, $this->key, $value);

        if ($result instanceof Config) {
            return $this;
        }

        return $result;
    }

    public function updateGroup($value)
    {
        return $this->configModifier->updateGroup(
            $value, array('`group` = ?' => $this->group, '`key` = ?' => $this->key)
        );
    }

    public function updateKey($value)
    {
        return $this->configModifier->updateKey(
            $value, array('`group` = ?' => $this->group, '`key` = ?' => $this->key)
        );
    }

    public function updateValue($value)
    {
        return $this->configModifier->updateValue(
            $value, array('`group` = ?' => $this->group, '`key` = ?' => $this->key)
        );
    }

    public function delete()
    {
        $result = $this->configModifier->delete($this->group, $this->key);

        if ($result instanceof Config) {
            return $this;
        }

        return $result;
    }

    //########################################
}