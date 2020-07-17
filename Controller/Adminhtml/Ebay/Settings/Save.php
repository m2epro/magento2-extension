<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Save
 */
class Save extends Settings
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        $this->getHelper('Component_Ebay_Configuration')->setConfigValues($this->getRequest()->getParams());
        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
