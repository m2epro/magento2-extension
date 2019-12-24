<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingEditTabs');
        // ---------------------------------------

        $this->setDestElementId('edit_form');
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $block = $this->createBlock('Amazon_Listing_Create_Selling_Form');
        $block->setUseFormContainer(false);
        $this->addTab(
            'selling',
            [
                'label'   => $this->__('Selling Settings'),
                'title'   => $this->__('Selling Settings'),
                'content' => $block->toHtml()
            ]
        );
        // ---------------------------------------

        // ---------------------------------------
        $block = $this->createBlock('Amazon_Listing_Create_Search_Form');
        $block->setUseFormContainer(false);
        $this->addTab(
            'search',
            [
                'label'   => $this->__('Search Settings'),
                'title'   => $this->__('Search Settings'),
                'content' => $block->toHtml()
            ]
        );
        // ---------------------------------------

        $this->setActiveTab($this->getRequest()->getParam('tab', 'selling'));

        return parent::_beforeToHtml();
    }

    //########################################
}
