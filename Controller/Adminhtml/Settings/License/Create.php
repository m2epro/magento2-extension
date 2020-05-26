<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\License\Create
 */
class Create extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->setAjaxContent($this->createBlock('Settings_Tabs_License_Create'));
            return $this->getResult();
        }

        $post = $this->getRequest()->getPostValue();

        $requiredKeys = [
            'email',
            'firstname',
            'lastname',
            'phone',
            'country',
            'city',
            'postal_code',
        ];

        $licenseData = [];
        foreach ($requiredKeys as $key) {
            if (!empty($post[$key])) {
                $licenseData[$key] = $this->getHelper('Data')->escapeJs(
                    $this->getHelper('Data')->escapeHtml($post[$key])
                );
                continue;
            }
            $this->setJsonContent([
                'success' => false,
                'message' => $this->__('You should fill all required fields.')
            ]);
            return $this->getResult();
        }

        $primaryConfig = $this->getHelper('Primary')->getConfig();
        $oldLicenseKey = $primaryConfig->getGroupValue('/license/', 'key');
        $primaryConfig->setGroupValue('/license/', 'key', '');

        $message = null;

        try {
            $licenseResult = $this->getHelper('Module\License')->obtainRecord(
                $licenseData['email'],
                $licenseData['firstname'],
                $licenseData['lastname'],
                $licenseData['country'],
                $licenseData['city'],
                $licenseData['postal_code'],
                $licenseData['phone']
            );
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $licenseResult = false;
            $message = $this->__($e->getMessage());
        }

        if (!$licenseResult) {
            $primaryConfig->setGroupValue('/license/', 'key', $oldLicenseKey);

            if (!$message) {
                $message = $this->__('License Creation is failed. Please contact M2E Pro Support for resolution.');
            }

            $this->setJsonContent([
                'success' => false,
                'message' => $message
            ]);

            return $this->getResult();
        }

        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            '/wizard/license_form_data/',
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $registry->setData('key', '/wizard/license_form_data/');
        $registry->setData('value', $this->getHelper('Data')->jsonEncode($licenseData));
        $registry->save();

        $licenseKey = $this->getHelper('Primary')->getConfig()->getGroupValue('/license/', 'key');

        $this->setJsonContent([
            'success'     => true,
            'message'     => $this->__('The License Key has been successfully created.'),
            'license_key' => $licenseKey
        ]);

        return $this->getResult();
    }

    //########################################
}
