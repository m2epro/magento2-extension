<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    //########################################

    public function execute()
    {
        $motorsAttributes = array();

        if ($motorsEpidsMotorAttribute = $this->getRequest()->getParam('motors_epids_motor_attribute')) {
            $motorsAttributes[] = $motorsEpidsMotorAttribute;
        }
        if ($motorsEpidsUkAttribute = $this->getRequest()->getParam('motors_epids_uk_attribute')) {
            $motorsAttributes[] = $motorsEpidsUkAttribute;
        }
        if ($motorsEpidsDeAttribute = $this->getRequest()->getParam('motors_epids_de_attribute')) {
            $motorsAttributes[] = $motorsEpidsDeAttribute;
        }
        if ($motorsKtypesAttribute = $this->getRequest()->getParam('motors_ktypes_attribute')) {
            $motorsAttributes[] = $motorsKtypesAttribute;
        }

        if (count($motorsAttributes) != count(array_unique($motorsAttributes))) {

            $this->setJsonContent([
                'success' => false,
                'messages' => [
                    ['error' => $this->__('Motors Attributes can not be the same.')]
                ]
            ]);
            return $this->getResult();
        }

        $this->getHelper('module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'epids_motor_attribute', $motorsEpidsMotorAttribute
        );
        $this->getHelper('module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'epids_uk_attribute', $motorsEpidsUkAttribute
        );
        $this->getHelper('module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'epids_de_attribute', $motorsEpidsDeAttribute
        );
        $this->getHelper('module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'ktypes_attribute', $motorsKtypesAttribute
        );

        $this->setAjaxContent($this->getHelper('Data')->jsonEncode([
            'success' => true
        ]), false);

        return $this->getResult();
    }

    //########################################
}