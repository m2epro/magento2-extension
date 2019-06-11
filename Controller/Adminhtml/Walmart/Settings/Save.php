<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

class Save extends Settings
{
    //########################################

    public function execute()
    {
        $this->getHelper('Component\Walmart\Configuration')
            ->setConfigValues($this->getRequest()->getParams());

        $this->setJsonContent([
            'success' => true
        ]);
        return $this->getResult();
    }

    //########################################
}