<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Button;

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