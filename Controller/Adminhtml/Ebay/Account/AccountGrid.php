<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\AccountGrid
 */
class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Grid $switcherBlock */
        $grid = $this->createBlock(
            'Ebay_Account_Grid'
        );

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}
