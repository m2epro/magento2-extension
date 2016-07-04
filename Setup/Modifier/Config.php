<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Modifier;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Setup\Modifier\Config\EntityFactory;
use Ess\M2ePro\Setup\Tables;
use Magento\Framework\Module\Setup;
use Ess\M2ePro\Setup\Modifier\Config\Entity;

class Config extends AbstractModifier
{
    protected $entityFactory;

    //########################################

    public function __construct(
        Setup $installer,
        HelperFactory $helperFactory,
        Tables $tablesObject,
        $tableName,
        EntityFactory $entityFactory
    ) {
        parent::__construct($installer, $helperFactory, $tablesObject, $tableName);

        $this->entityFactory = $entityFactory;
    }

    //########################################

    /**
     * @param string $group
     * @param string $key
     * @return mixed
     * @throws Setup
     */
    public function getRow($group, $key)
    {
        $query = $this->connection
                      ->select()
                      ->from($this->tableName)
                      ->where('`group` = ?', $this->prepareGroup($group))
                      ->where('`key` = ?', $this->prepareKey($key));

        return $this->connection->fetchRow($query);
    }

    /**
     * @param string $group
     * @param string $key
     * @return Entity
     */
    public function getEntity($group, $key)
    {
        return $this->entityFactory->create([
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
     * @throws Setup
     */
    public function isExists($group, $key = NULL)
    {
        $query = $this->connection
                      ->select()
                      ->from($this->tableName)
                      ->where('`group` = ?', $this->prepareGroup($group));

        if (!is_null($key)) {
            $query->where('`key` = ?', $this->prepareKey($key));
        }

        $row = $this->connection->fetchOne($query);
        return !empty($row);
    }

    // ---------------------------------------

    /**
     * @param string $group
     * @param string $key
     * @param string|null $value
     * @param string|null $notice
     * @return $this|int
     * @throws Setup
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
     * @throws Setup
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
     * @throws Setup
     */
    public function delete($group, $key = NULL)
    {
        if (!$this->isExists($group, $key)) {
            return $this;
        }

        $where = array(
            '`group` = ?' => $this->prepareGroup($group)
        );

        if (!is_null($key)) {
            $where['`key` = ?'] = $this->prepareKey($key);
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

    /**
     * @throws Setup
     */
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
        return trim($key, '/ ');
    }

    //########################################

    private function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s', gmdate('U'));
    }

    //########################################
}