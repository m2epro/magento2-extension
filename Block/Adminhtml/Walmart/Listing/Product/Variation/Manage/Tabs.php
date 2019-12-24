<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartVariationProductManageTabs');
        // ---------------------------------------

        $this->setDestElementId('variation_product_manage_tabs_container');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    // ---------------------------------------

    protected function _beforeToHtml()
    {
        $this->addTab('variations', [
            'label'   => $this->__('Child Products'),
            'title'   => $this->__('Child Products'),
            'content' => $this->createBlock('Walmart_Listing_Product_Variation_Manage_Tabs_Variations')
                ->setListingProduct($this->getListingProduct())
                ->toHtml()
        ]);

        $settingsBlock = $this->createBlock('Walmart_Listing_Product_Variation_Manage_Tabs_Settings_Form')
            ->setListingProduct($this->getListingProduct());
        $settingsBlock->calculateWarnings();

        $this->addTab('settings', [
            'label'   => $this->__('Settings'),
            'title'   => $this->__('Settings'),
            'content' => $settingsBlock->toHtml(),
            'class'   => (!empty($settingsBlock->getMessages())) ? 'listing-view-warning-icon' : ''
        ]);

        $this->addTab('vocabulary', [
            'label'   => $this->__('Advanced'),
            'title'   => $this->__('Advanced'),
            'content' => $this->createBlock('Walmart_Listing_Product_Variation_Manage_Tabs_Vocabulary')
                ->setListingProduct($this->getListingProduct())
                ->toHtml()
        ]);

        if (!empty($settingsBlock->getMessages())) {
            $this->setActiveTab('settings');
        } else {
            $this->setActiveTab('variations');
        }

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return '<div id="variation_manage_tabs_container">' . parent::_toHtml() .
            '<div id="variation_product_manage_tabs_container"></div></div>';
    }

    //########################################
}
