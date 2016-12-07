<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Marketplace;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher
{
    //########################################

    protected function loadItems()
    {
        parent::loadItems();

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Switcher $accountSwitcher */
        $accountSwitcher = $this->createBlock('Amazon\Account\Switcher')->setData([
            'component_mode' => $this->getData('component_mode')
        ]);

        if (!is_null($accountSwitcher->getSelectedParam())) {
            $this->hasDefaultOption = false;
            $this->setIsDisabled(true);
        }
    }

    //########################################
}