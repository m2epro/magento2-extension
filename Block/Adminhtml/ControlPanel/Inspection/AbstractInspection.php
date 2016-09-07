<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class AbstractInspection extends AbstractBlock
{
    //########################################

    public function isShown()
    {
        return true;
    }

    //########################################
}