<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Database\Modifier;

use Ess\M2ePro\Model\Setup\Database\Modifier\Config\Entity;

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

        if (is_null($group)) {
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
        return $this->modelFactory->getObject('Setup\Database\Modifier\Config\Entity', [
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
    public function isExists($group, $key = NULL)
    {
        $group = $this->prepareGroup($group);
        $key   = $this->prepareKey($key);

        $query = $this->connection
                      ->select()
                      ->from($this->tableName);

        if (is_null($group)) {
            $query->where('`group` IS NULL');
        } else {
            $query->where('`group` = ?', $group);
        }

        if (!is_null($key)) {
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
    public function insert($group, $key, $value = NULL, $notice = NULL)
    {
        if ($this->isExists($group, $key)) {
            return $this;
        }

        $preparedData = array(
            'group' => $this->prepareGroup($group),
            'key'   => $this->prepareKey($key),
        );

        !is_null($value) && $preparedData['value'] = $value;
        !is_null($notice) && $preparedData['notice'] = $notice;

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
        $field == 'key'   && $value = $this->prepareKey($value);

        $preparedData = array(
            $field        => $value,
            'update_date' => $this->getCurrentDateTime()
        );

        return $this->connection->update($this->tableName, $preparedData, $where);
    }

    /**
     * @param string $group
     * @param string|null $key
     * @return $this|int
     */
    public function delete($group, $key = NULL)
    {
        if (!$this->isExists($group, $key)) {
            return $this;
        }

        $group = $this->prepareGroup($group);
        $key   = $this->prepareKey($key);

        if (is_null($group)) {
            $where = array('`group` IS NULL');
        } else {
            $where = array('`group` = ?' => $group);
        }

        if (!is_null($key)) {
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
        $tempData = array();
        $deleteData = array();

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
        if (is_null($group)) {
            return $group;
        }

        return '/' . trim($group, '/ ') . '/';
    }

    private function prepareKey($key)
    {
        if (is_null($key)) {
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