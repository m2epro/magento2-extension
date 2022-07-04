<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Maintenance
{
    public const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::m2epro_maintenance';
    private const MAINTENANCE_CONFIG_PATH = 'm2epro/maintenance';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var array */
    private $cache = [];

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfig(self::MAINTENANCE_CONFIG_PATH);
    }

    /**
     * @return void
     */
    public function enable(): void
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, 1);
    }

    /**
     * @return void
     */
    public function disable(): void
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, 0);
    }

    /**
     * @param $path
     *
     * @return mixed|string
     */
    private function getConfig($path)
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        $configDataTableName = $this->databaseHelper
            ->getTableNameWithPrefix('core_config_data');
        $select = $this->resourceConnection->getConnection()
                                           ->select()
                                           ->from($configDataTableName, 'value')
                                           ->where('scope = ?', 'default')
                                           ->where('scope_id = ?', 0)
                                           ->where('path = ?', $path);

        return $this->cache[$path] = $this->resourceConnection->getConnection()->fetchOne($select);
    }

    /**
     * @param $path
     * @param $value
     *
     * @return void
     */
    private function setConfig($path, $value): void
    {
        $connection = $this->resourceConnection->getConnection();

        $configDataTableName = $this->databaseHelper
            ->getTableNameWithPrefix('core_config_data');
        if ($this->getConfig($path) === false) {
            $connection->insert(
                $configDataTableName,
                [
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => $path,
                    'value'    => $value,
                ]
            );
        } else {
            $connection->update(
                $configDataTableName,
                ['value' => $value],
                [
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => $path,
                ]
            );
        }

        unset($this->cache[$path]);
    }
}
