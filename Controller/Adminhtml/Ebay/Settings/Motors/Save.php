<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors\Save
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

        try {
            $this->getHelper('Component_Ebay_Configuration')->setConfigValues($this->getRequest()->getParams());
            $this->setJsonContent(['success' => true]);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->setJsonContent(
                [
                    'success'  => false,
                    'messages' => [
                        ['error' => $this->__($e->getMessage())]
                    ]
                ]
            );
        }

        return $this->getResult();
    }

    //########################################
}
