<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing;

class TypeSwitcher extends \Ess\M2ePro\Block\Adminhtml\Listing\TypeSwitcher
{
    //########################################

    public function getSwitchCallback()
    {
        $typeM2ePro = self::LISTING_TYPE_M2E_PRO;
        $typeM2eProUrl = $this->getUrl(
            "*/{$this->getData('component_mode')}_log_listing_{$typeM2ePro}/*",
            array('_current' => true)
        );

        $typeOther = self::LISTING_TYPE_LISTING_OTHER;
        $typeOtherUrl = $this->getUrl(
            "*/{$this->getData('component_mode')}_log_listing_{$typeOther}/*",
            array('_current' => true)
        );

        return <<<JS
var typeM2eProUrl = '{$typeM2eProUrl}';
var typeOtherUrl  = '{$typeOtherUrl}';
var switchUrl     = typeM2eProUrl;

if (this.value == '{$typeOther}') {
    switchUrl = typeOtherUrl;
}

setLocation(switchUrl);
JS;
    }

    public function getSelectedParam()
    {
        if (strpos($this->getRequest()->getControllerName(), self::LISTING_TYPE_LISTING_OTHER) !== false) {
            return self::LISTING_TYPE_LISTING_OTHER;
        }

        return self::LISTING_TYPE_M2E_PRO;
    }

    //########################################
}