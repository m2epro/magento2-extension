<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class AccountGrid
 * @package Ess\M2ePro\Controller\Adminhtml\Amazon\Account
 */
class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Grid $switcherBlock */
        $grid = $this->createBlock('Amazon_Account_Grid');
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}
