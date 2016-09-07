<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GetGrid extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Account', $id);

        $this->getHelper('Data\GlobalData')->setValue('edit_account', $model);

        $this->setAjaxContent($this->createBlock('Ebay\Account\Edit\Tabs\Feedback\Template\Grid'));

        return $this->getResult();
    }
}