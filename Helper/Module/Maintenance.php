<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

use Ess\M2ePro\Helper\Factory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class \Ess\M2ePro\Helper\Module\Maintenance
 */
class Maintenance extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAINTENANCE_CONFIG_PATH = 'm2epro/maintenance';
    const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::m2epro_maintenance';

    private $resourceConnection;
    private $cache = [];

    //########################################

    public function __construct(
        Factory $helperFactory,
        Context $context,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($helperFactory, $context);
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->getConfig(self::MAINTENANCE_CONFIG_PATH);
    }

    public function enable()
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, 1);
    }

    public function disable()
    {
        $this->setConfig(self::MAINTENANCE_CONFIG_PATH, 0);
    }

    //########################################

    protected function getConfig($path)
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', $path);

        return $this->cache[$path] = $this->resourceConnection->getConnection()->fetchOne($select);
    }

    protected function setConfig($path, $value)
    {
        $connection = $this->resourceConnection->getConnection();

        if ($this->getConfig($path) === false) {
            $connection->insert(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                [
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => $path,
                    'value'    => $value
                ]
            );
        } else {
            $connection->update(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                ['value' => $value],
                [
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => $path
                ]
            );
        }

        unset($this->cache[$path]);
    }

    //########################################
}
