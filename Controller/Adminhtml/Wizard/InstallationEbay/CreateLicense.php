<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\CreateLicense
 */
class CreateLicense extends InstallationEbay
{
    public function execute()
    {
        if (!empty($this->getHelper('Module')->getRegistry()->getValueFromJson('/wizard/license_form_data/'))) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

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
            if ($tempValue = $this->getRequest()->getParam($key)) {
                $licenseData[$key] = $this->getHelper('Data')->escapeJs(
                    $this->getHelper('Data')->escapeHtml($tempValue)
                );
                continue;
            }

            $response = [
                'status' => false,
                'message' => $this->__('You should fill all required fields.')
            ];

            $this->setJsonContent($response);
            return $this->getResult();
        }

        if ($this->getHelper('Module\License')->getKey()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

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
            if (!$message) {
                $message = $this->__('License Creation is failed. Please contact M2E Pro Support for resolution.');
            }

            $this->setJsonContent([
                'status'  => $licenseResult,
                'message' => $message
            ]);

            return $this->getResult();
        }

        $this->getHelper('Module')->getRegistry()->setValue('/wizard/license_form_data/', $licenseData);

        $this->setJsonContent(['status' => $licenseResult]);
        return $this->getResult();
    }
}
