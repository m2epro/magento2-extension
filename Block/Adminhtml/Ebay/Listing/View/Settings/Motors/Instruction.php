<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors;

/**
 * Class Instruction
 * @package Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors
 */
class Instruction extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebay/listing/view/settings/motors/instruction.phtml');
    }

    //########################################
}
