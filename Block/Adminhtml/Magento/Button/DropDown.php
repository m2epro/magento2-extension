<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Button;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown
 */
class DropDown extends \Magento\Backend\Block\Widget\Button\SplitButton
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Ess_M2ePro::magento/button/dropdown.phtml');
    }

    //########################################
}
