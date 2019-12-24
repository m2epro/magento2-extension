<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Component;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Component\Switcher
 */
abstract class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    //########################################

    public function getParamName()
    {
        if ($this->getData('component_mode') === null) {
            return parent::getParamName();
        }

        return $this->getData('component_mode') . ucfirst($this->paramName);
    }

    public function getSwitchCallbackName()
    {
        return 'switch' . ucfirst($this->getParamName());
    }

    //########################################
}
