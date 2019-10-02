<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage;

/**
 * Class Tabs
 * @package Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Manage
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    private $errorsCount;

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
        $this->errorsCount = count($settingsBlock->getMessages());

        $settingsBlockLabel = $this->__('Settings');
        $settingsBlockTitle = $this->__('Settings');

        $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/'. $settingsBlock->getMessagesType() .'.png');
        $iconTitle = $this->__('Action required.');
        $iconStyle = 'vertical-align: middle;';

        if ($this->errorsCount == 0) {
            $iconStyle .= 'display:none;';
        }

        $problemIcon = <<<HTML
<img style="{$iconStyle}" src="{$iconPath}" title="{$iconTitle}" alt="" width="16" height="15">&nbsp;
HTML;

        $this->addTab('settings', [
            'label'   => $problemIcon . $settingsBlockLabel,
            'title'   => $settingsBlockTitle,
            'content' => $settingsBlock->toHtml()
        ]);

        $this->addTab('vocabulary', [
            'label'   => $this->__('Advanced'),
            'title'   => $this->__('Advanced'),
            'content' => $this->createBlock('Walmart_Listing_Product_Variation_Manage_Tabs_Vocabulary')
                ->setListingProduct($this->getListingProduct())
                ->toHtml()
        ]);

        if ($this->errorsCount > 0) {
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
