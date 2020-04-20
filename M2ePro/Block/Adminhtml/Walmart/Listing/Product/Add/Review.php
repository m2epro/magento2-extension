<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\Review
 */
class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $source;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductReview');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/add/review.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
            '',
            ['data' => ['listing' => $listing]]
        );

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListing', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ]);

        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'label'   => $this->__('Go To The Listing'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ]);
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListingAndList', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ]);

        $buttonBlock = $this->createBlock('Magento\Button')
            ->setData([
                'label'   => $this->__('List Added Products Now'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ]);
        $this->setChild('list', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        if ($this->getSource() === SourceModeBlock::MODE_OTHER) {
            $url = $this->getUrl('*/walmart_listing_other/view', [
                'account'     => $listing->getAccountId(),
                'marketplace' => $listing->getMarketplaceId(),
            ]);

            $buttonBlock = $this->createBlock('Magento\Button')
                ->setData([
                    'label'   => $this->__('Back to 3rd Party Listing'),
                    'onclick' => 'setLocation(\''.$url.'\');',
                    'class' => 'action primary'
                ]);
            $this->setChild('back_to_listing_other', $buttonBlock);
        }
        // ---------------------------------------
    }

    //########################################

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    //########################################
}
