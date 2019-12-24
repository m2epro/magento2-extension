<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\MagentoInventory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\MagentoInventory\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/product/force_qty/',
            'mode',
            (int)$post['force_qty_mode']
        );

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/product/force_qty/',
            'value',
            (int)$post['force_qty_value']
        );

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/magento/attribute/',
            'price_type_converting',
            (int)$post['price_type_converting_mode']
        );

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
