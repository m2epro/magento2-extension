<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product;

class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingProductReview');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/product/review.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing\View\Header','', [
            'data' => ['listing' => $this->getHelper('Data\GlobalData')->getValue('review_listing')]
        ]);

        $this->setChild('view_header', $viewHeaderBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing/view', array(
            'id' => $this->getRequest()->getParam('id')
        ));
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Go To The Listing'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class'   => 'primary'
            ));
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $addedProductsIds = $this->getHelper('Data\Session')->getValue('added_products_ids');
        $url = $this->getUrl('*/ebay_listing/previewItems', array(
            'currentProductId' => $addedProductsIds[0],
            'productIds' => implode(',', $addedProductsIds),
        ));
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Preview Added Products Now'),
                'onclick' => 'window.open(\''.$url.'\').focus();',
                'class'   => 'primary go'
            ));
        $this->setChild('preview', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing/view', array(
            'id' => $this->getRequest()->getParam('id'),
            'do_list' => true
        ));
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label' => $this->__('List Added Products Now'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'primary'
            ));
        $this->getRequest()->getParam('disable_list', false) && $buttonBlock->setData('style','display: none');
        $this->setChild('save_and_list', $buttonBlock);
        // ---------------------------------------
    }

    //########################################
}