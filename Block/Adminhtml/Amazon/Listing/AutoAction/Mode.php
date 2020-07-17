<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\AbstractMode
 */
class Mode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\AbstractMode
{
    //########################################

    public function getHelpPageUrl()
    {
        return $this->getHelper('Module\Support')
            ->getDocumentationArticleUrl('x/kAYtAQ');
    }

    //########################################
}
