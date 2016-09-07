<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Moving;

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
                'Listing\Moving\FailedProducts\Grid','',
                ['data' => ['grid_url' => $this->getData('grid_url')]]
            )
        );
        // ---------------------------------------

        parent::_beforeToHtml();
    }

    //########################################
}