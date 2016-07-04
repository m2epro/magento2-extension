<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Amazon;

class Component extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    public function getComponents()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getComponents());
    }

    public function getComponentsTitles()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getComponentsTitles());
    }

    // ---------------------------------------

    public function getEnabledComponents()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getEnabledComponents());
    }

    public function getEnabledComponentsTitles()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getEnabledComponentsTitles());
    }

    // ---------------------------------------

    public function getDisabledComponents()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getDisabledComponents());
    }

    public function getDisabledComponentsTitles()
    {
        return $this->removeEbayFromComponentsArray($this->getHelper('Component')->getDisabledComponentsTitles());
    }

    //########################################

    public function isAmazonDefault()
    {
        return $this->getDefaultComponent() == \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    // ---------------------------------------

    public function getDefaultComponent()
    {
        $defaultComponent = $this->getHelper('Module')->getConfig()->getGroupValue(
            '/view/amazon/component/', 'default'
        );
        return in_array($defaultComponent, $this->getEnabledComponents())
            ? $defaultComponent : \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################

    private function removeEbayFromComponentsArray($components)
    {
        if (!array_key_exists(0, $components)) {
            unset($components[\Ess\M2ePro\Helper\Component\Ebay::NICK]);
            return $components;
        }

        $resultComponents = [];
        foreach ($components as $component) {
            if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                continue;
            }
            $resultComponents[] = $component;
        }

        return $resultComponents;
    }

    //########################################
}