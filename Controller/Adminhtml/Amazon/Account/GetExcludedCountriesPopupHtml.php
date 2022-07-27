<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class GetExcludedCountriesPopupHtml extends Account
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order\ExcludedCountries $block */
        $block = $this->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order\ExcludedCountries::class);
        $block->setData('selected_countries', explode(',', $this->getRequest()->getParam('selected_countries')));

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}
