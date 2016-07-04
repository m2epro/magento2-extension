<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search;

class Main extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('amazon/listing/product/search/main.phtml');
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $data = array(
            'id'    => 'productSearch_submit_button',
            'label' => $this->__('Search'),
            'class' => 'productSearch_submit_button submit action primary'
        );
        $buttonSubmitBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('productSearch_submit_button', $buttonSubmitBlock);
        // ---------------------------------------

        parent::_beforeToHtml();
    }

//    protected function _toHtml()
//    {
//        $vocabularyAttributesBlock = $this->getLayout()->createBlock(
//            'M2ePro/adminhtml_common_amazon_listing_variation_product_vocabularyAttributesPopup'
//        );
//
//        $vocabularyOptionsBlock = $this->getLayout()->createBlock(
//            'M2ePro/adminhtml_common_amazon_listing_variation_product_vocabularyOptionsPopup'
//        );
//
//        return $vocabularyAttributesBlock->toHtml() . $vocabularyOptionsBlock->toHtml() . parent::_toHtml();
//    }

    //########################################
}