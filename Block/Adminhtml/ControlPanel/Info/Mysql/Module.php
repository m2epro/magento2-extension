<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\Module\Database\Structure;

class Module extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabaseModule');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/mysql/module.phtml');
    }

    //########################################

    public function getInfoTables()
    {
        $tablesData = array_merge($this->getConfigTables(),
                                  $this->getLocksAndChangeTables(),
                                  $this->getAdditionalTables());

        /** @var Structure $helper */
        $helper = $this->getHelper('Module\Database\Structure');

        $tablesInfo = array();
        foreach ($tablesData as $category => $tables) {
            foreach ($tables as $tableName) {

                $tablesInfo[$category][$tableName] = array(
                    'count' => 0, 'url'   => '#'
                );

                if (!$helper->isTableReady($tableName)) {
                    continue;
                }

                $tablesInfo[$category][$tableName]['count'] = $helper->getCountOfRecords($tableName);
                $tablesInfo[$category][$tableName]['url'] = $this->getUrl(
                    '*/controlPanel_database/manageTable', array('table' => $tableName)
                );
            }
        }

        return $tablesInfo;
    }

    //########################################

    private function getConfigTables()
    {
        return array(
            'Config' => array(
                'm2epro_module_config',
                'm2epro_primary_config',
                'm2epro_synchronization_config',
                'm2epro_cache_config'
            )
        );
    }

    private function getLocksAndChangeTables()
    {
        return array(
            'Additional' => array(
                'm2epro_lock_item',
                'm2epro_product_change',
                'm2epro_order_change',
                'm2epro_operation_history'
            )
        );
    }

    private function getAdditionalTables()
    {
        return array(
            'Processing' => array(
                'm2epro_processing',
                'm2epro_processing_lock',
                'm2epro_request_pending_single',
                'm2epro_request_pending_partial',
                'm2epro_connector_pending_requester_single',
                'm2epro_connector_pending_requester_partial',
            )
        );
    }

    //########################################
}