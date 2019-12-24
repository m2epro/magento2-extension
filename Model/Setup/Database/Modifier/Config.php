<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Database\Modifier;

use Ess\M2ePro\Model\Setup\Database\Modifier\Config\Entity;

/**
 * Class \Ess\M2ePro\Model\Setup\Database\Modifier\Config
 */
class Config extends AbstractModifier
{
    //########################################

    /**
     * @param string $group
     * @param string $key
     * @return mixed
     */
    public function getRow($group, $key)
    {
        $group = $this->prepareGroup($group);
        $key   = $this->prepareKey($key);

        $query = $this->connection
                      ->select()
                      ->from($this->tableName)
                      ->where('`key` = ?', $key);

        if ($group === null) {
            $query->where('`group` IS NULL');
        } else {
            $query->where('`group` = ?', $group);
        }

        return $this->connection->fetchRow($query);
    }

    /**
     * @param string $group
     * @param string $key
     * @return Entity
     */
    public function getEntity($group, $key)
    {
        return $this->modelFactory->getObject('Setup_Database_Modifier_Config_Entity', [
            'configModifier' => $this,
            'group'          => $group,
            'key'            => $key,
        ]);
    }

    //########################################

    /**
     * @param string $group
     * @param string|null $key
     * @return bool
     */
    public function isExists($group, $key = null)
    {
        $group = $this->prepareGroup($group);
        $key   = $this->prepareKey($key);

        $query = $this->connection
                      ->select()
                      ->from($this->tableName);

        if ($group === null) {
            $query->where('`group` IS NULL');
        } else {
            $query->where('`group` = ?', $group);
        }

        if ($key !== null) {
            $query->where('`key` = ?', $key);
        }

        return !empty($this->connection->fetchOne($query));
    }

    // ---------------------------------------

    /**
     * @param string $group
     * @param string $key
     * @param string|null $value
     * @param string|null $notice
     * @return $this|int
     */
    public function insert($group, $key, $value = null, $notice = null)
    {
        if ($this->isExists($group, $key)) {
            return $this;
        }

        $preparedData = [
            'group' => $this->prepareGroup($group),
            'key'   => $this->prepareKey($key),
        ];

        $value !== null && $preparedData['value'] = $value;
        $notice !== null && $preparedData['notice'] = $notice;

        $preparedData['update_date'] = $this->getCurrentDateTime();
        $preparedData['create_date'] = $this->getCurrentDateTime();

        return $this->connection->insert($this->tableName, $preparedData);
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $where
     * @return int
     */
    public function update($field, $value, $where)
    {
        $field == 'group' && $value = $this->prepareGroup($value);
        $field == 'key' && $value = $this->prepareKey($value);

        $preparedData = [
            $field        => $value,
            'update_date' => $this->getCurrentDateTime()
        ];

        return $this->connection->update($this->tableName, $preparedData, $where);
    }

    /**
     * @param string $group
     * @param string|null $key
     * @return $this|int
     */
    public function delete($group, $key = null)
    {
        if (!$this->isExists($group, $key)) {
            return $this;
        }

        $group = $this->prepareGroup($group);
        $key   = $this->prepareKey($key);

        if ($group === null) {
            $where = ['`group` IS NULL'];
        } else {
            $where = ['`group` = ?' => $group];
        }

        if ($key !== null) {
            $where['`key` = ?'] = $key;
        }

        return $this->connection->delete($this->tableName, $where);
    }

    //########################################

    /**
     * @param string $value
     * @param string $where
     * @return int
     */
    public function updateGroup($value, $where)
    {
        return $this->update('group', $value, $where);
    }

    /**
     * @param string $value
     * @param string $where
     * @return int
     */
    public function updateKey($value, $where)
    {
        return $this->update('key', $value, $where);
    }

    /**
     * @param string $value
     * @param string $where
     * @return int
     */
    public function updateValue($value, $where)
    {
        return $this->update('value', $value, $where);
    }

    //########################################

    public function removeDuplicates()
    {
        $tempData = [];
        $deleteData = [];

        $configRows = $this->connection
                           ->query("SELECT `id`, `group`, `key`
                                    FROM `{$this->tableName}`
                                    ORDER BY `id` ASC")
                           ->fetchAll();

        foreach ($configRows as $configRow) {
            $tempName = strtolower($configRow['group'] .'|'. $configRow['key']);

            if (in_array($tempName, $tempData)) {
                $deleteData[] = (int)$configRow['id'];
            } else {
                $tempData[] = $tempName;
            }
        }

        if (!empty($deleteData)) {
            $this->connection
                 ->query("DELETE FROM `{$this->tableName}`
                          WHERE `id` IN (".implode(',', $deleteData).')');
        }
    }

    //########################################

    private function prepareGroup($group)
    {
        if ($group === null) {
            return $group;
        }

        return '/' . trim($group, '/ ') . '/';
    }

    private function prepareKey($key)
    {
        if ($key === null) {
            return $key;
        }

        return trim($key, '/ ');
    }

    //########################################

    private function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s', gmdate('U'));
    }

    //########################################
}
