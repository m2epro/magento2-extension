<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual;

class Manage extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductVariationEdit');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/variation/individual/manage.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->_prepareButtons();

        return parent::_beforeToHtml();
    }

    //########################################

    protected function _prepareButtons()
    {
        $buttonBlock = $this->createBlock('Magento\Button')->setData(array(
            'label' => $this->__('Add Another Variation'),
            'onclick' => '',
            'class' => 'action primary',
            'id' => 'add_more_variation_button'
        ));
        $this->setChild('add_more_variation_button', $buttonBlock);

        // ---------------------------------------

        $onClick = 'AmazonListingProductVariationObj.manageGenerateAction(false);';
        $buttonBlock = $this->createBlock('Magento\Button')->setData(array(
            'label' => $this->__('Generate All Variations'),
            'onclick' => $onClick,
            'class' => 'action primary',
            'id' => 'variation_manage_generate_all'
        ));
        $this->setChild('variation_manage_generate_all', $buttonBlock);

        $onClick = 'AmazonListingProductVariationObj.manageGenerateAction(true);';
        $buttonBlock = $this->createBlock('Magento\Button')->setData(array(
            'label' => $this->__('Generate Non-Existing Variations'),
            'onclick' => $onClick,
            'class' => 'action primary',
            'id' => 'variation_manage_generate_unique'
        ));
        $this->setChild('variation_manage_generate_unique', $buttonBlock);
    }

    //########################################
}