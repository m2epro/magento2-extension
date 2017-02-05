<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Account\Switcher
{
    //########################################

    public function getSwitchCallback()
    {
        $marketplaces = [];

        foreach ($this->getItems()[$this->getData('component_mode')]['value'] as $item) {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->activeRecordFactory->getCachedObjectLoaded('Account', $item['value']);

            $marketplaces[$account->getId()] = $account->getChildObject()->getMarketplaceId();
        }

        $encodedMarketplaces = $this->getHelper('Data')->jsonEncode($marketplaces);

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Marketplace\Switcher $marketplaceSwitcher */
        $marketplaceSwitcher = $this->createBlock('Amazon\Marketplace\Switcher')->setData([
            'component_mode' => $this->getData('component_mode')
        ]);

        return <<<JS
var switchUrl = '{$this->getSwitchUrl()}';
var paramName = '{$this->getParamName()}';
var paramPlaceHolder = '{$this->getParamPlaceHolder()}';

var marketplaceParamName = '{$marketplaceSwitcher->getParamName()}';
var marketplaces = {$encodedMarketplaces};

if (this.value == '{$this->getDefaultOptionValue()}') {
    switchUrl = switchUrl.replace(paramName +'/'+ paramPlaceHolder +'/', '');
} else {
    switchUrl = switchUrl.replace(paramPlaceHolder, this.value);

    var re = new RegExp(marketplaceParamName + '\/\\\\d+\/');
    switchUrl = switchUrl.replace(re, '');
    switchUrl += marketplaceParamName + '/' + marketplaces[this.value] + '/';
}

setLocation(switchUrl);
JS;
    }

    //########################################
}