<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Grid $switcherBlock */
        $grid = $this->getLayout()->createBlock(
            'Ess\\M2ePro\\Block\\Adminhtml\\Ebay\\Account\\Grid'
        );

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}