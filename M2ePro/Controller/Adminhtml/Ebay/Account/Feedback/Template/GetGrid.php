<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template\GetGrid
 */
class GetGrid extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Account', $id);

        $this->getHelper('Data\GlobalData')->setValue('edit_account', $model);

        $this->setAjaxContent($this->createBlock('Ebay_Account_Edit_Tabs_Feedback_Template_Grid'));

        return $this->getResult();
    }
}
