<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee\Product
 */
class Product extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewFeePreview');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/view/ebay/fee/product.phtml');
    }

    public function getFees()
    {
        if (empty($this->_data['fees']) || !is_array($this->_data['fees'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Fees are not set.');
        }

        return $this->_data['fees'];
    }

    public function getTotalFee()
    {
        $fees = $this->getFees();

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $fees['listing_fee']['currency'],
            $fees['listing_fee']['fee']
        );
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $details = $this->createBlock('Ebay_Listing_View_Ebay_Fee_Details');
        $details->setData('fees', $this->getFees());
        $details->setData('product_name', $this->getData('product_name'));

        $this->setChild('details', $details);
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}
