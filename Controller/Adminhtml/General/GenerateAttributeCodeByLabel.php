<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\GenerateAttributeCodeByLabel
 */
class GenerateAttributeCodeByLabel extends General
{
    //########################################

    public function execute()
    {
        $label = $this->getRequest()->getParam('store_label');
        $this->setAjaxContent(\Ess\M2ePro\Model\Magento\Attribute\Builder::generateCodeByLabel($label), false);
        return $this->getResult();
    }

    //########################################
}
