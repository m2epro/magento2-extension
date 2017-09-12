<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

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