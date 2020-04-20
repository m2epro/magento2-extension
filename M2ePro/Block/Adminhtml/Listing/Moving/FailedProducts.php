<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Moving;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Moving\FailedProducts
 */
class FailedProducts extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_template = 'listing/moving/failedProducts.phtml';

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------

        $this->setChild(
            'failedProducts_grid',
            $this->createBlock(
                'Listing_Moving_FailedProducts_Grid',
                '',
                ['data' => ['grid_url' => $this->getData('grid_url')]]
            )
        );
        // ---------------------------------------

        parent::_beforeToHtml();
    }

    //########################################
}
