<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetAddSpecificsGridHtml
 */
class GetAddSpecificsGridHtml extends Description
{
    //########################################

    public function execute()
    {
        $gridBlock = $this->prepareGridBlock();
        $this->setAjaxContent($gridBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}
