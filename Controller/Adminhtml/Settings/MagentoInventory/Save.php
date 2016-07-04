<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\MagentoInventory;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setAjaxContent(json_encode(['success' => false]), false);
            return $this->getResult();
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/product/force_qty/', 'mode',
            (int)$post['force_qty_mode']
        );

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/product/force_qty/', 'value',
            (int)$post['force_qty_value']
        );

        $this->setAjaxContent(json_encode(['success' => true]), false);
        return $this->getResult();
    }

    //########################################
}