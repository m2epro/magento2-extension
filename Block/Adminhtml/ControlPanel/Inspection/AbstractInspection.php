<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class AbstractInspection
 * @package Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection
 */
abstract class AbstractInspection extends AbstractBlock
{
    //########################################

    public function isShown()
    {
        return true;
    }

    //########################################
}
