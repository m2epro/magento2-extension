<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\AccountGrid
 */
class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Account\Grid $switcherBlock */
        $grid = $this->createBlock('Walmart_Account_Grid');
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}
