<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\CreateLicense
 */
class CreateLicense extends InstallationWalmart
{
    public function execute()
    {
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
                $licenseData[$key] = $tempValue;
                continue;
            }

            $response = [
                'status'  => false,
                'message' => $this->__('You should fill all required fields.')
            ];
            $this->setJsonContent($response);
            return $this->getResult();
        }

        $registry = $this->activeRecordFactory->getObject('Registry');

        $registry->setData('key', '/wizard/license_form_data/');
        $registry->setData('value', $this->getHelper('Data')->jsonEncode($licenseData));
        $registry->save();

        if ($this->getHelper('Module\License')->getKey()) {
            $this->setJsonContent(['status' => true]);
            return $this->getResult();
        }

        $licenseResult = $this->getHelper('Module\License')->obtainRecord(
            $licenseData['email'],
            $licenseData['firstname'],
            $licenseData['lastname'],
            $licenseData['country'],
            $licenseData['city'],
            $licenseData['postal_code'],
            $licenseData['phone']
        );

        $this->setJsonContent(['status' => $licenseResult]);
        return $this->getResult();
    }
}
