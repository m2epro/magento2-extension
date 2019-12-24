<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Database\Modifier\Config;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Setup\Database\Modifier\Config;

/**
 * Class \Ess\M2ePro\Model\Setup\Database\Modifier\Config\Entity
 */
class Entity extends AbstractModel
{
    private $group = null;
    private $key = null;

    /**
     * @var Config
     */
    protected $configModifier;

    //########################################

    public function __construct(
        Config $configModifier,
        $group,
        $key,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->configModifier = $configModifier;

        $this->group = $group;
        $this->key   = $key;

        parent::__construct($helperFactory, $modelFactory, $data);
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
        return isset($row['value']) ? $row['value'] : null;
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
        return $this->configModifier->updateGroup($value, $this->getWhereConditions());
    }

    public function updateKey($value)
    {
        return $this->configModifier->updateKey($value, $this->getWhereConditions());
    }

    public function updateValue($value)
    {
        return $this->configModifier->updateValue($value, $this->getWhereConditions());
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

    private function getWhereConditions()
    {
        if ($this->group === null) {
            $conditions = ['`group` IS NULL'];
        } else {
            $conditions = ['`group` = ?' => $this->group];
        }

        $conditions['`key` = ?'] = $this->key;

        return $conditions;
    }

    //########################################
}
