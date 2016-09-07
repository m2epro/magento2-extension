<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee;

class Errors extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewFeeErrors');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/view/ebay/fee/errors.phtml');
    }

    public function getErrors()
    {
        if (empty($this->_data['errors']) || !is_array($this->_data['errors'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Errors are not set.');
        }

        return $this->_data['errors'];
    }

    //########################################
}