<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Controller\Adminhtml\System\Config;

class Edit
{
    //########################################

    public function afterExecute($subject, $result)
    {
        $result->getConfig()->addPageAsset("Ess_M2ePro::css/help_block.css");
        return $result;
    }

    //########################################
}