<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage;

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
        $this->setId('amazonVariationProductManageTabs');
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
        $this->addTab('variations', array(
            'label'   => $this->__('Child Products'),
            'title'   => $this->__('Child Products'),
            'content' => $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Variations')
                ->setListingProduct($this->getListingProduct())
                ->toHtml()
        ));

        $settingsBlock = $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Settings\Form')
            ->setListingProduct($this->getListingProduct());
        $settingsBlock->calculateWarnings();

        $settingsBlockLabel = $this->__('Settings');
        $settingsBlockTitle = $this->__('Settings');

        $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/'. $settingsBlock->getMessagesType() .'.png');
        $iconTitle = $this->__('Action required.');
        $iconStyle = 'vertical-align: middle;';

        if (count($settingsBlock->getMessages()) == 0) {
            $iconStyle .= 'display:none;';
        }

        $problemIcon = <<<HTML
<img style="{$iconStyle}" src="{$iconPath}" title="{$iconTitle}" alt="" width="16" height="15">&nbsp;
HTML;

        $this->addTab('settings', array(
            'label'   => $problemIcon . $settingsBlockLabel,
            'title'   => $settingsBlockTitle,
            'content' => $settingsBlock->toHtml()
        ));

        $this->addTab('vocabulary', array(
            'label'   => $this->__('Advanced'),
            'title'   => $this->__('Advanced'),
            'content' => $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Vocabulary')
                ->setListingProduct($this->getListingProduct())
                ->toHtml()
        ));

        $generalId = $this->getListingProduct()->getChildObject()->getGeneralId();
        if (empty($generalId) && $this->getListingProduct()->getChildObject()->isGeneralIdOwner()) {
            $this->setActiveTab('settings');
        } else {
            $this->setActiveTab('variations');
        }

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $generalId = $this->getListingProduct()->getChildObject()->getGeneralId();

        $showChildProducts = (int)(
            !(empty($generalId) && $this->getListingProduct()->getChildObject()->isGeneralIdOwner())
        );

        $this->js->add(
<<<JS
    if (!{$showChildProducts}) {
        jQuery(jQuery('#amazonVariationProductManageTabs').find("li")[0]).hide();
    }
JS
        );

        return '<div id="variation_manage_tabs_container">' . parent::_toHtml() .
            '<div id="variation_product_manage_tabs_container"></div></div>';
    }

    //########################################
}