<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Log extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingLog');

        $this->_controller = 'adminhtml_ebay_listing_log';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        // ---------------------------------------
    }

    //########################################

    public function getListingId()
    {
        return $this->getRequest()->getParam('id', false);
    }

    // ---------------------------------------

    /** @var \Ess\M2ePro\Model\Listing $listing */
    protected $listing = NULL;

    /**
     * @return \Ess\M2ePro\Model\Listing|null
     */
    public function getListing()
    {
        if (is_null($this->listing)) {
            $this->listing = $this->activeRecordFactory->getObjectLoaded('Listing', $this->getListingId());
        }

        return $this->listing;
    }

    //########################################

    public function getListingProductId()
    {
        return $this->getRequest()->getParam('listing_product_id', false);
    }

    // ---------------------------------------

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct = NULL;

    /**
     * @return \Ess\M2ePro\Model\Listing\Product|null
     */
    public function getListingProduct()
    {
        if (is_null($this->listingProduct)) {
            $this->listingProduct = $this->activeRecordFactory
                ->getObjectLoaded('Listing\Product', $this->getListingProductId());
        }

        return $this->listingProduct;
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        if (!$this->getListingId() && !$this->getListingProductId()) {

            $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
        }
        // ---------------------------------------
    }

    //########################################
}