<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Ebay;

class Component extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    public function getComponents()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getComponents());
    }

    public function getComponentsTitles()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getComponentsTitles());
    }

    // ---------------------------------------

    public function getEnabledComponents()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getEnabledComponents());
    }

    public function getEnabledComponentsTitles()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getEnabledComponentsTitles());
    }

    // ---------------------------------------

    public function getDisabledComponents()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getDisabledComponents());
    }

    public function getDisabledComponentsTitles()
    {
        return $this->removeAmazonFromComponentsArray($this->getHelper('Component')->getDisabledComponentsTitles());
    }

    //########################################

    private function removeAmazonFromComponentsArray($components)
    {
        if (isset($components[\Ess\M2ePro\Helper\Component\Ebay::NICK])) {
            return [
                \Ess\M2ePro\Helper\Component\Ebay::NICK => $components[\Ess\M2ePro\Helper\Component\Ebay::NICK]
            ];
        }

        foreach ($components as $component) {
            if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                return [\Ess\M2ePro\Helper\Component\Ebay::NICK];
            }
        }

        return array();
    }

    //########################################
}