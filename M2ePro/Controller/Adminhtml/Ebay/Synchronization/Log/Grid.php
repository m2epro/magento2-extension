<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization\Log;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization\Log\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    //########################################

    public function execute()
    {
        $response = $this->createBlock('Ebay_Synchronization_Log_Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}
