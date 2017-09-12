<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

class Create extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPostValue();

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

                if (!empty($post[$key])) {
                    $licenseData[$key] = $post[$key];
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

            $licenseResult = $this->getHelper('Module\License')->obtainRecord(
                $licenseData['email'],
                $licenseData['firstname'], $licenseData['lastname'],
                $licenseData['country'], $licenseData['city'],
                $licenseData['postal_code'], $licenseData['phone']
            );

            if ($licenseResult) {

                $registry = $this->activeRecordFactory->getObjectLoaded(
                    'Registry', '/wizard/license_form_data/', 'key', false
                );

                if (is_null($registry)) {
                    $registry = $this->activeRecordFactory->getObject('Registry');
                }

                $registry->setData('key', '/wizard/license_form_data/');
                $registry->setData('value', $this->getHelper('Data')->jsonEncode($licenseData));
                $registry->save();

                $licenseKey = $this->getHelper('Primary')->getConfig()->getGroupValue('/license/', 'key');
                $this->setJsonContent(array(
                    'success' => true,
                    'message' => $this->__('The License Key has been successfully created.'),
                    'license_key' => $licenseKey
                ));
            } else {
                $primaryConfig->setGroupValue('/license/','key', $oldLicenseKey);

                $this->setJsonContent(array(
                    'success' => false,
                    'message' => $this->__('Internal Server Error')
                ));
            }

            return $this->getResult();
        }

        $this->setAjaxContent($this->createBlock('Settings\Tabs\License\Create'));
        return $this->getResult();
    }

    //########################################
}