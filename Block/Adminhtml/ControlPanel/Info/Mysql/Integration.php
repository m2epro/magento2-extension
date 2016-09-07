<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\Module\Database\Structure;

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
        $tablesData = array_merge($this->getGeneralTables(),
                                  $this->getEbayTables(),
                                  $this->getAmazonTables());

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

    private function getGeneralTables()
    {
        return array(
            'General' => array(
                'm2epro_account',
                'm2epro_listing',
                'm2epro_listing_product',
                'm2epro_listing_other'
            )
        );
    }

    private function getAmazonTables()
    {
        return array(
            'Amazon' => array(
                'm2epro_amazon_account',
                'm2epro_amazon_item',
                'm2epro_amazon_listing',
                'm2epro_amazon_listing_product',
                'm2epro_amazon_listing_other'
            )
        );
    }

    private function getEbayTables()
    {
        return array(
            'Ebay' => array(
                'm2epro_ebay_account',
                'm2epro_ebay_item',
                'm2epro_ebay_listing',
                'm2epro_ebay_listing_product',
                'm2epro_ebay_listing_other'
            )
        );
    }

    //########################################
}