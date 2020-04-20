<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\License\Change
 */
class Change extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPostValue();
            $primaryConfig = $this->getHelper('Primary')->getConfig();

            // Save settings
            // ---------------------------------------
            $key = strip_tags($post['new_license_key']);
            $primaryConfig->setGroupValue('/license/', 'key', (string)$key);
            // ---------------------------------------

            try {
                $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                    $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
                );
            } catch (\Exception $e) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                return $this->getResult();
            }

            /** @var \Ess\M2ePro\Helper\Module\License $licenseHelper */
            $licenseHelper = $this->getHelper('Module\License');
            if (!$licenseHelper->getKey() || !$licenseHelper->getDomain() || !$licenseHelper->getIp()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $this->__('You are trying to use the unknown License Key.')
                ]);
                return $this->getResult();
            }

            $this->setJsonContent([
                'success' => true,
                'message' => $this->__('The License Key has been successfully updated.')
            ]);
            return $this->getResult();
        }

        $this->setAjaxContent($this->createBlock('Settings_Tabs_License_Change'));
        return $this->getResult();
    }

    //########################################
}
