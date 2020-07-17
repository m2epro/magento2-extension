<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Overview
 */
class Overview extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelOverview');
        $this->setTemplate('control_panel/tabs/overview.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('actual_info', $this->createBlock(
            'ControlPanel_Info_Actual'
        ));

        $this->setChild('license_info', $this->createBlock(
            'ControlPanel_Info_License'
        ));

        //----------------------------------------

        $this->setChild('cron_info', $this->createBlock(
            'ControlPanel_Inspection_Cron'
        ));

        $this->setChild('version_info', $this->createBlock(
            'ControlPanel_Inspection_VersionInfo'
        ));

        //----------------------------------------

        $this->setChild(
            'database_general',
            $this->createBlock(
                'ControlPanel_Info_MysqlTables',
                '',
                ['data' => [
                    'tables_list' => [
                        'Config' => [
                            'm2epro_config',
                            'm2epro_registry'
                        ],
                        'General' => [
                            'm2epro_account',
                            'm2epro_listing',
                            'm2epro_listing_product',
                            'm2epro_listing_other'
                        ],
                        'Processing' => [
                            'm2epro_processing',
                            'm2epro_processing_lock',
                            'm2epro_request_pending_single',
                            'm2epro_request_pending_partial',
                            'm2epro_connector_pending_requester_single',
                            'm2epro_connector_pending_requester_partial',
                        ],
                        'Additional' => [
                            'm2epro_lock_item',
                            'm2epro_system_log',
                            'm2epro_listing_product_instruction',
                            'm2epro_listing_product_scheduled_action',
                            'm2epro_order_change',
                            'm2epro_operation_history',
                        ],
                    ]
                ]]
            )
        );

        $this->setChild(
            'database_components',
            $this->createBlock(
                'ControlPanel_Info_MysqlTables',
                '',
                ['data' => [
                    'tables_list' => [
                        'Amazon' => [
                            'm2epro_amazon_account',
                            'm2epro_amazon_item',
                            'm2epro_amazon_listing',
                            'm2epro_amazon_listing_product',
                            'm2epro_amazon_listing_other'
                        ],
                        'Ebay' => [
                            'm2epro_ebay_account',
                            'm2epro_ebay_item',
                            'm2epro_ebay_listing',
                            'm2epro_ebay_listing_product',
                            'm2epro_ebay_listing_other'
                        ],
                        'Walmart' => [
                            'm2epro_walmart_account',
                            'm2epro_walmart_item',
                            'm2epro_walmart_listing',
                            'm2epro_walmart_listing_product',
                            'm2epro_walmart_listing_other'
                        ]
                    ]
                ]]
            )
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
