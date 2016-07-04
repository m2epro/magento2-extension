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
        $data = array(
            'id'    => 'failedProducts_continue_button',
            'label' => $this->__('Continue'),
            'class' => 'submit'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('failedProducts_continue_button',$buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $data = array(
            'id'    => 'failedProducts_back_button',
            'label' => $this->__('Back'),
            'class' => 'scalable back',
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('failedProducts_back_button',$buttonBlock);
        // ---------------------------------------

        // ---------------------------------------

        $this->setChild(
            'failedProducts_grid',
            $this->createBlock(
                'Listing\Moving\FailedProducts\Grid','',
                array('grid_url' => $this->getData('grid_url'))
            )
        );
        // ---------------------------------------

        parent::_beforeToHtml();
    }

    //########################################
}