<?php

namespace Ess\M2ePro\Block\Adminhtml\Component;

abstract class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    //########################################

    public function getParamName()
    {
        if (is_null($this->getData('component_mode'))) {
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