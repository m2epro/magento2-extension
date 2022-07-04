<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\AccountGrid
 */
class AccountGrid extends Account
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Grid $switcherBlock */
        $grid = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Grid::class);
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}
