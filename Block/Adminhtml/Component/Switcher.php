<?php

namespace Ess\M2ePro\Block\Adminhtml\Component;

abstract class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    //########################################

    protected function getComponentLabel($label)
    {
        $label = trim($label);

        if (is_null($this->getData('component_mode')) ||
            ($this->getData('component_mode') != \Ess\M2ePro\Helper\Component\Ebay::NICK &&
                count($this->getHelper('View\Amazon\Component')->getEnabledComponents()) == 1)) {

            return trim(preg_replace(array('/%component%/', '/\s{2,}/'), ' ', $label));
        }

        $componentTitles = $this->getHelper('Component')->getComponentsTitles();

        $component = '';
        if (isset($componentTitles[$this->getData('component_mode')])) {
            $component = $componentTitles[$this->getData('component_mode')];
        }

        if (strpos($label, '%component%') === false) {
            return "{$component} {$label}";
        }

        return str_replace('%component%', $component, $label);
    }

    //########################################

    public function getParamName()
    {
        if (is_null($this->getData('component_mode'))) {
            return parent::getParamName();
        }

        return $this->getData('component_mode') . ucfirst($this->paramName);
    }

    public function getSwitchUrl()
    {
        //todo
//        $params = array(
//            '_current' => true,
//            $this->getParamName() => $this->getParamPlaceHolder()
//        );
//
//        $tabId = Ess_M2ePro_Block_Adminhtml_Common_Component_Abstract::getTabIdByComponent(
//            $this->getData('component_mode')
//        );
//
//        if (!is_null($tabId)) {
//            $params['tab'] = $tabId;
//        }
//
//        $controllerName = $this->getData('controller_name') ? $this->getData('controller_name') : '*';
//
//        return $this->getUrl("*/{$controllerName}/*", $params);
        return parent::getSwitchUrl();
    }

    public function getSwitchCallback()
    {
        return 'switch' . ucfirst($this->getParamName());
    }

    //########################################
}