<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/view/ebay/feedbacks/notification/', 'mode',
            (int)$this->getRequest()->getParam('view_ebay_feedbacks_notification_mode')
        );

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

        $this->setAjaxContent($this->getHelper('Data')->jsonEncode([
            'success' => true
        ]), false);

        return $this->getResult();
    }

    //########################################
}