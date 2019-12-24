<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\Component
 */
class Component extends AbstractHelper
{
    //########################################

    /**
     * @return Component\Ebay
     */
    public function getEbayComponentHelper()
    {
        return $this->getHelper('Component\Ebay');
    }

    /**
     * @return Component\Amazon
     */
    public function getAmazonComponentHelper()
    {
        return $this->getHelper('Component\Amazon');
    }

    /**
     * @return Component\Walmart
     */
    public function getWalmartComponentHelper()
    {
        return $this->getHelper('Component\Walmart');
    }

    //########################################

    public function getComponents()
    {
        return [
            Component\Ebay::NICK,
            Component\Amazon::NICK,
            Component\Walmart::NICK,
        ];
    }

    // ---------------------------------------

    public function getComponentsTitles()
    {
        return [
            Component\Ebay::NICK    => $this->getHelper('Component\Ebay')->getTitle(),
            Component\Amazon::NICK  => $this->getHelper('Component\Amazon')->getTitle(),
            Component\Walmart::NICK => $this->getHelper('Component\Walmart')->getTitle(),
        ];
    }

    //########################################

    public function getEnabledComponents()
    {
        $components = [];

        if ($this->getHelper('Component\Ebay')->isEnabled()) {
            $components[] = Component\Ebay::NICK;
        }
        if ($this->getHelper('Component\Amazon')->isEnabled()) {
            $components[] = Component\Amazon::NICK;
        }
        if ($this->getHelper('Component\Walmart')->isEnabled()) {
            $components[] = Component\Walmart::NICK;
        }

        return $components;
    }

    public function getEnabledComponentByView($viewNick)
    {
        $enabledComponents = $this->getEnabledComponents();

        if ($viewNick == \Ess\M2ePro\Helper\View\Ebay::NICK &&
            in_array(Component\Ebay::NICK, $enabledComponents)
        ) {
            return Component\Ebay::NICK;
        }

        if ($viewNick == \Ess\M2ePro\Helper\View\Amazon::NICK &&
            in_array(Component\Amazon::NICK, $enabledComponents)
        ) {
            return Component\Amazon::NICK;
        }

        if ($viewNick == \Ess\M2ePro\Helper\View\Walmart::NICK &&
            in_array(Component\Walmart::NICK, $enabledComponents)
        ) {
            return Component\Walmart::NICK;
        }
    }

    // ---------------------------------------

    public function getEnabledComponentsTitles()
    {
        $components = [];

        if ($this->getHelper('Component\Ebay')->isEnabled()) {
            $components[Component\Ebay::NICK] = $this->getHelper('Component\Ebay')->getTitle();
        }
        if ($this->getHelper('Component\Amazon')->isEnabled()) {
            $components[Component\Amazon::NICK] = $this->getHelper('Component\Amazon')->getTitle();
        }
        if ($this->getHelper('Component\Walmart')->isEnabled()) {
            $components[Component\Walmart::NICK] = $this->getHelper('Component\Walmart')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getDisabledComponents()
    {
        $components = [];

        if (!$this->getHelper('Component\Ebay')->isEnabled()) {
            $components[] = Component\Ebay::NICK;
        }
        if (!$this->getHelper('Component\Amazon')->isEnabled()) {
            $components[] = Component\Amazon::NICK;
        }
        if (!$this->getHelper('Component\Walmart')->isEnabled()) {
            $components[] = Component\Walmart::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    public function getDisabledComponentsTitles()
    {
        $components = [];

        if (!$this->getHelper('Component\Ebay')->isEnabled()) {
            $components[Component\Ebay::NICK] = $this->getHelper('Component\Ebay')->getTitle();
        }
        if (!$this->getHelper('Component\Amazon')->isEnabled()) {
            $components[Component\Amazon::NICK] = $this->getHelper('Component\Amazon')->getTitle();
        }
        if (!$this->getHelper('Component\Walmart')->isEnabled()) {
            $components[Component\Walmart::NICK] = $this->getHelper('Component\Walmart')->getTitle();
        }

        return $components;
    }

    //########################################

    public function getComponentTitle($component)
    {
        $title = null;

        switch ($component) {
            case Component\Ebay::NICK:
                $title = $this->getEbayComponentHelper()->getTitle();
                break;
            case Component\Amazon::NICK:
                $title = $this->getAmazonComponentHelper()->getTitle();
                break;
            case Component\Walmart::NICK:
                $title = $this->getWalmartComponentHelper()->getTitle();
                break;
        }

        return $title;
    }

    //########################################
}
