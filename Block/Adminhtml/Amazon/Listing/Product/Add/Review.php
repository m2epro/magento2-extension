<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductReview');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/add/review.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------

        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->createBlock(
            'Listing\View\Header','', ['data' => ['listing' => $listing]]
        );

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListing', array(
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ));

        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('Go To The Listing'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ));
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListingAndList', array(
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ));

        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData(array(
                'label'   => $this->__('List Added Products Now'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ));
        $this->setChild('list', $buttonBlock);
        // ---------------------------------------
    }

    //########################################
}