<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class Save extends Settings
{
    //########################################

    public function execute()
    {
//       TODO NOT SUPPORTED FEATURES
//        $this->getHelper('Module')->getConfig()->setGroupValue(
//            '/view/ebay/feedbacks/notification/', 'mode',
//            (int)$this->getRequest()->getParam('view_ebay_feedbacks_notification_mode')
//        );

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/ebay/template/category/', 'use_last_specifics',
            (int)$this->getRequest()->getParam('use_last_specifics_mode')
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/ebay/connector/listing/', 'check_the_same_product_already_listed',
            (int)$this->getRequest()->getParam('check_the_same_product_already_listed_mode')
        );
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/ebay/description/', 'upload_images_mode',
            (int)$this->getRequest()->getParam('upload_images_mode')
        );

        $sellingCurrency = $this->getRequest()->getParam('selling_currency');
        if (!empty($sellingCurrency)) {
            foreach ($sellingCurrency as $code => $value) {
                $this->getHelper('Module')->getConfig()->setGroupValue(
                    '/ebay/selling/currency/', $code, (string)$value
                );
            }
        }

        // TODO NOT SUPPORTED FEATURES
        /*$motorsEpidsAttribute = $this->getRequest()->getParam('motors_epids_attribute');
        $motorsKtypesAttribute = $this->getRequest()->getParam('motors_ktypes_attribute');

        if (!empty($motorsKtypesAttribute) && !empty($motorsEpidsAttribute) &&
            $motorsEpidsAttribute == $motorsKtypesAttribute
        ) {
            $this->_getSession()->addError(
                $this->getHelper('Data')->__('ePIDs and kTypes Attributes can not be the same.')
            );
            $this->_redirectUrl($this->_getRefererUrl());
            return;
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'epids_attribute',
            $motorsEpidsAttribute
        );

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/ebay/motors/', 'ktypes_attribute',
            $motorsKtypesAttribute
        );*/

        $this->setAjaxContent(json_encode([
            'success' => true
        ]), false);
        return $this->getResult();
    }

    //########################################
}