<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Backups;

class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    const GENERAL_SETTINGS_ID = 'general';

    private $availableTables = NULL;

    protected $resource;

    /** @var \Ess\M2ePro\Model\Config\Cache */
    private $cache = NULL;
    private $cacheGroup = '/backup/settings/';

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resource = $resource;
        $this->cache = $cacheConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings(array $settings)
    {
        if (isset($settings['tables']) && is_array($settings['tables'])) {
            foreach ($settings['tables'] as $tableName => $tableSettings) {
                if (!is_array($tableSettings)) {
                    continue;
                }

                foreach ($tableSettings as $tableSettingKey => $tableSettingValue) {
                    $this->setSetting($tableSettingKey, $tableSettingValue, $tableName);
                }
            }
        }

        if (isset($settings[self::GENERAL_SETTINGS_ID]) && is_array($settings[self::GENERAL_SETTINGS_ID])) {
            foreach ($settings[self::GENERAL_SETTINGS_ID] as $generalSettingKey => $generalSettingValue) {
                $this->setSetting($generalSettingKey, $generalSettingValue, self::GENERAL_SETTINGS_ID);
            }
        }

        return $this;
    }

    public function deleteSettings($tableName = NULL)
    {
        $group = $this->prepareSettingGroup($tableName);
        $this->cache->deleteAllGroupValues($group);

        return $this;
    }

    //########################################

    public function setSetting($key, $value, $tableName = NULL)
    {
        $group = $this->prepareSettingGroup($tableName);
        $this->cache->setGroupValue($group, $key, $value);

        return $this;
    }

    public function getSetting($key, $tableName = NULL)
    {
        $group = $this->prepareSettingGroup($tableName);

        return $this->cache->getGroupValue($group, $key);
    }

    //########################################

    private function prepareSettingGroup($tableName = NULL)
    {
        $group = $this->cacheGroup;

        if (!is_null($tableName)) {
            $group .= $tableName . '/';
        }

        return $group;
    }

    //########################################

    public function canBackupTable($tableName)
    {
        if (!in_array($tableName, $this->getHelper('Module\Database\Structure')->getMySqlTables())) {
            return false;
        }

        $interval = $this->getSetting('interval', $tableName);

        if (is_null($interval) || (int)$interval <= 0) {
            return false;
        }

        return true;
    }

    public function isTimeToBackupTable($tableName)
    {
        $interval = (int)$this->getSetting('interval', $tableName);

        if ($interval <= 0) {
            return false;
        }

        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $lastAccessDate = $this->cache->getGroupValue('/backup/'.$tableName.'/', 'last_access');

        if (!is_null($lastAccessDate) && $currentTimeStamp < strtotime($lastAccessDate) + (int)$interval) {
            return false;
        }

        return true;
    }

    public function updateTableLastAccessDate($tableName)
    {
        $this->cache->setGroupValue(
            '/backup/'.$tableName.'/', 'last_access', $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    //########################################

    public function getTableDump($tableName, $columns = '*', $count = NULL, $offset = NULL)
    {
        $tableName = $this->resource->getTableName($tableName);

        if (!in_array($tableName, $this->getAvailableTables())) {
            return array();
        }

        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from($tableName, $columns)
            ->limit($count, $offset);
        $query = $connection->query($select);

        return $query->fetchAll();
    }

    //########################################

    private function getAvailableTables()
    {
        if (is_null($this->availableTables)) {
            $this->availableTables = $this->getHelper('Magento')->getMySqlTables();
        }
        return $this->availableTables;
    }

    //########################################
}