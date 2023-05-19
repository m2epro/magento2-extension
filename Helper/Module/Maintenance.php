<?php

namespace Ess\M2ePro\Helper\Module;

class Maintenance
{
    public const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::m2epro_maintenance';

    private const MAINTENANCE_CONFIG_PATH = 'm2epro/maintenance';

    private const VALUE_DISABLED = 0;
    private const VALUE_ENABLED = 1;
    private const VALUE_ENABLED_DUE_LOW_MAGENTO_VERSION = 2;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var array */
    private $cache = [];

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->databaseHelper = $databaseHelper;
    }

    public function enable(): void
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, self::VALUE_ENABLED);
    }

    public function isEnabled(): bool
    {
        return (bool)$this->getConfig(self::MAINTENANCE_CONFIG_PATH);
    }

    public function enableDueLowMagentoVersion(): void
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, self::VALUE_ENABLED_DUE_LOW_MAGENTO_VERSION);
    }

    public function isEnabledDueLowMagentoVersion(): bool
    {
        return (int)$this->getConfig(self::MAINTENANCE_CONFIG_PATH) === self::VALUE_ENABLED_DUE_LOW_MAGENTO_VERSION;
    }

    public function disable(): void
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, self::VALUE_DISABLED);
    }

    private function getConfig(string $path)
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

    private function setConfig(string $path, $value): void
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
