<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class CreateLicense extends InstallationAmazon
{
    public function execute()
    {
        $requiredKeys = array(
            'email',
            'firstname',
            'lastname',
            'phone',
            'country',
            'city',
            'postal_code',
        );

        $licenseData = array();
        foreach ($requiredKeys as $key) {

            if ($tempValue = $this->getRequest()->getParam($key)) {
                $licenseData[$key] = $tempValue;
                continue;
            }

            $response = array(
                'status'  => false,
                'message' => $this->__('You should fill all required fields.')
            );
            $this->setJsonContent($response);
            return $this->getResult();
        }

        $registry = $this->activeRecordFactory->getObject('Registry');

        $registry->setData('key', '/wizard/license_form_data/');
        $registry->setData('value', $this->getHelper('Data')->jsonEncode($licenseData));
        $registry->save();

        if ($this->getHelper('Module\License')->getKey()) {
            $this->setJsonContent(array('status' => true));
            return $this->getResult();
        }

        $licenseResult = $this->getHelper('Module\License')->obtainRecord(
            $licenseData['email'],
            $licenseData['firstname'], $licenseData['lastname'],
            $licenseData['country'], $licenseData['city'],
            $licenseData['postal_code'], $licenseData['phone']
        );

        $this->setJsonContent(array('status' => $licenseResult));
        return $this->getResult();
    }
}