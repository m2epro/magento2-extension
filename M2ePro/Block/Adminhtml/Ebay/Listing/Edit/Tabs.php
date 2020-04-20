<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEditTabs');
        // ---------------------------------------

        $this->setDestElementId('edit_form');
    }

    //########################################

    public function getAllowedTabs()
    {
        if (!isset($this->_data['allowed_tabs']) || !is_array($this->_data['allowed_tabs'])) {
            return [];
        }

        return $this->_data['allowed_tabs'];
    }

    private function isTabAllowed($tab)
    {
        $allowedTabs = $this->getAllowedTabs();

        if (count($allowedTabs) == 0) {
            return true;
        }

        if (in_array($tab, $allowedTabs)) {
            return true;
        }

        return false;
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        if ($this->isTabAllowed('general')) {
            $block = $this->createBlock(
                'Ebay_Listing_Edit_Tabs_General',
                '',
                ['policy_localization' => $this->getData('policy_localization')]
            );
            $this->addTab(
                'general',
                [
                    'label'   => $this->__('Payment and Shipping'),
                    'title'   => $this->__('Payment and Shipping'),
                    'content' => $block->toHtml(),
                ]
            );
        }
        // ---------------------------------------

        // ---------------------------------------
        if ($this->isTabAllowed('selling')) {
            $block = $this->createBlock('Ebay_Listing_Edit_Tabs_Selling');
            $this->addTab(
                'selling',
                [
                    'label'   => $this->__('Selling'),
                    'title'   => $this->__('Selling'),
                    'content' => $block->toHtml(),
                ]
            );
        }
        // ---------------------------------------

        // ---------------------------------------
        if ($this->isTabAllowed('synchronization')) {
            $block = $this->createBlock('Ebay_Listing_Edit_Tabs_Synchronization');
            $this->addTab(
                'synchronization',
                [
                    'label'   => $this->__('Synchronization'),
                    'title'   => $this->__('Synchronization'),
                    'content' => $block->toHtml(),
                ]
            );
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}
