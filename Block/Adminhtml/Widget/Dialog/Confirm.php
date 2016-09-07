<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Widget\Dialog;

class Confirm extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('widgetConfirm');
        // ---------------------------------------

        $this->setTemplate('widget/dialog/confirm.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $data = array(
            'class'   => 'ok_button',
            'label'   => $this->__('Confirm'),
            'onclick' => 'Dialog.okCallback();',
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('ok_button', $buttonBlock);
        // ---------------------------------------
    }

    //########################################
}