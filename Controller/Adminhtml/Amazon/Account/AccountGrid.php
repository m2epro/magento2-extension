<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Grid $switcherBlock */
        $grid = $this->createBlock('Amazon\\Account\\Grid');
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}