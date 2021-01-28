<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Review
 */
class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $source;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingProductReview');
        $this->setTemplate('ebay/listing/product/review.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $listing = $this->getHelper('Data\GlobalData')->getValue('review_listing');

        // ---------------------------------------
        $viewHeaderBlock = $this->createBlock('Listing_View_Header', '', [
            'data' => ['listing' => $listing]
        ]);

        $this->setChild('view_header', $viewHeaderBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing/view', [
            'id' => $this->getRequest()->getParam('id')
        ]);
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'id'   => $this->__('go_to_the_listing'),
                'label'   => $this->__('Go To The Listing'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class'   => 'primary'
            ]);
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $addedProductsIds = $this->getHelper('Data\Session')->getValue('added_products_ids');
        $url = $this->getUrl('*/ebay_listing/previewItems', [
            'currentProductId' => $addedProductsIds[0],
            'productIds' => implode(',', $addedProductsIds),
        ]);
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'label'   => $this->__('Preview Added Products Now'),
                'onclick' => 'window.open(\''.$url.'\').focus();',
                'class'   => 'primary go'
            ]);
        $this->setChild('preview', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing/view', [
            'id' => $this->getRequest()->getParam('id'),
            'do_list' => true
        ]);
        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'label' => $this->__('List Added Products Now'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'primary'
            ]);
        $this->getRequest()->getParam('disable_list', false) && $buttonBlock->setData('style', 'display: none');
        $this->setChild('save_and_list', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        if ($this->getSource() === SourceModeBlock::MODE_OTHER) {
            $url = $this->getUrl('*/ebay_listing_other/view', [
                    'account'     => $listing->getAccountId(),
                    'marketplace' => $listing->getMarketplaceId(),
            ]);
            $buttonBlock = $this->createBlock('Magento\Button')
                ->setData([
                    'label' => $this->__('Back to Unmanaged Listing'),
                    'onclick' => 'setLocation(\''.$url.'\');',
                    'class'   => 'primary go'
                ]);
            $this->setChild('back_to_listing_other', $buttonBlock);
        }

        // ---------------------------------------
    }

    //########################################

    public function setSource($value)
    {
        $this->source = $value;
    }

    public function getSource()
    {
        return $this->source;
    }

    //########################################
}
