<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\Module\Database\Structure;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql\Integration
 */
class Integration extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabaseIntegration');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/mysql/integration.phtml');
    }

    //########################################

    public function getInfoTables()
    {
        $tablesData = array_merge(
            $this->getGeneralTables(),
            $this->getEbayTables(),
            $this->getAmazonTables(),
            $this->getWalmartTables()
        );

        /** @var Structure $helper */
        $helper = $this->getHelper('Module_Database_Structure');

        $tablesInfo = [];
        foreach ($tablesData as $category => $tables) {
            foreach ($tables as $tableName) {
                $tablesInfo[$category][$tableName] = [
                    'count' => 0, 'url'   => '#'
                ];

                if (!$helper->isTableReady($tableName)) {
                    continue;
                }

                $tablesInfo[$category][$tableName]['count'] = $helper->getCountOfRecords($tableName);
                $tablesInfo[$category][$tableName]['url'] = $this->getUrl(
                    '*/controlPanel_database/manageTable',
                    ['table' => $tableName]
                );
            }
        }

        return $tablesInfo;
    }

    //########################################

    private function getGeneralTables()
    {
        return [
            'General' => [
                'm2epro_account',
                'm2epro_listing',
                'm2epro_listing_product',
                'm2epro_listing_other'
            ]
        ];
    }

    private function getAmazonTables()
    {
        return [
            'Amazon' => [
                'm2epro_amazon_account',
                'm2epro_amazon_item',
                'm2epro_amazon_listing',
                'm2epro_amazon_listing_product',
                'm2epro_amazon_listing_other'
            ]
        ];
    }

    private function getEbayTables()
    {
        return [
            'Ebay' => [
                'm2epro_ebay_account',
                'm2epro_ebay_item',
                'm2epro_ebay_listing',
                'm2epro_ebay_listing_product',
                'm2epro_ebay_listing_other'
            ]
        ];
    }

    private function getWalmartTables()
    {
        return [
            'Walmart' => [
                'm2epro_walmart_account',
                'm2epro_walmart_item',
                'm2epro_walmart_listing',
                'm2epro_walmart_listing_product',
                'm2epro_walmart_listing_other'
            ]
        ];
    }

    //########################################
}
