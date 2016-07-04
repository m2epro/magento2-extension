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
                $this->setAjaxContent(json_encode([
                    'success' => false,
                    'message' => $this->__('You should fill all required fields.')
                ]), false);
                return $this->getResult();
            }

            $registry = $this->activeRecordFactory->getObjectLoaded(
                'Registry', '/wizard/license_form_data/', 'key', false
            );

            if (is_null($registry)) {
                $registry = $this->activeRecordFactory->getObject('Registry');
            }

            $earlierFormData = $registry->getData('value');

            if (!empty($earlierFormData)) {
                $earlierFormData = json_decode($earlierFormData, true);

                if ($earlierFormData == $licenseData) {

                    $this->setAjaxContent(json_encode(array(
                        'success' => true,
                        'message' => $this->__('The License Key has been successfully created.')
                    )), false);
                    return $this->getResult();
                }
            }

            $primaryConfig = $this->getHelper('Primary')->getConfig();
            $oldLicenseKey = $primaryConfig->getGroupValue(
                '/'.$this->getHelper('Module')->getName().'/license/','key'
            );
            $primaryConfig->setGroupValue('/'.$this->getHelper('Module')->getName().'/license/','key','');

            $licenseResult = $this->getHelper('Module\License')->obtainRecord(
                $licenseData['email'],
                $licenseData['firstname'], $licenseData['lastname'],
                $licenseData['country'], $licenseData['city'], $licenseData['postal_code']
            );

            if ($licenseResult) {

                $registry->setData('key', '/wizard/license_form_data/');
                $registry->setData('value', json_encode($licenseData));
                $registry->save();

                $licenseKey = $this->getHelper('Primary')->getConfig()->getGroupValue(
                    '/'.$this->getHelper('Module')->getName().'/license/','key'
                );
                $this->setAjaxContent(json_encode(array(
                    'success' => true,
                    'message' => $this->__('The License Key has been successfully created.'),
                    'license_key' => $licenseKey
                )), false);
            } else {
                $primaryConfig->setGroupValue(
                    '/'.$this->getHelper('Module')->getName().'/license/','key', $oldLicenseKey
                );

                $this->setAjaxContent(json_encode(array(
                    'success' => false,
                    'message' => $this->__('Internal Server Error')
                )), false);
            }

            return $this->getResult();
        }

        $this->setAjaxContent($this->createBlock('Settings\Tabs\License\Create'));
        return $this->getResult();
    }

    //########################################
}