<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product;

/**
 * Trait \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\GridTrait
 */
trait GridTrait
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    protected function getExcludedActionTitles()
    {
        return [
            \Ess\M2ePro\Model\Listing\Log::ACTION_TRANSLATE_PRODUCT => '',
        ];
    }

    //########################################
}
