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
    const CONFIG_PATH = 'm2epro/maintenance';
    const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::m2epro_maintenance';
    const MENU_POSITION = 30;

    private $resourceConnection;

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
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', self::CONFIG_PATH);

        return (bool)$this->resourceConnection->getConnection()->fetchOne($select);
    }

    //########################################

    public function enable()
    {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'), 'value')
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', self::CONFIG_PATH);

        if ($this->resourceConnection->getConnection()->fetchOne($select) === false) {
            $this->resourceConnection->getConnection()->insert(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => self::CONFIG_PATH,
                    'value' => 1
                ]
            );
            return;
        }

        $this->resourceConnection->getConnection()->update(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
            ['value' => 1],
            [
                'scope = ?' => 'default',
                'scope_id = ?' => 0,
                'path = ?' => self::CONFIG_PATH,
            ]
        );
    }

    public function disable()
    {
        $this->resourceConnection->getConnection()->update(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
            ['value' => 0],
            [
                'scope = ?' => 'default',
                'scope_id = ?' => 0,
                'path = ?' => self::CONFIG_PATH,
            ]
        );
    }

    //########################################
}
