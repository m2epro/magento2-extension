<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

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

    //########################################

    public function getComponents()
    {
        return [
            Component\Ebay::NICK,
            Component\Amazon::NICK
        ];
    }

    // ---------------------------------------

    public function getComponentsTitles()
    {
        return [
            Component\Ebay::NICK   => $this->getHelper('Component\Ebay')->getTitle(),
            Component\Amazon::NICK => $this->getHelper('Component\Amazon')->getTitle()
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

        return $components;
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

        return $components;
    }

    //########################################

    public function getComponentTitle($component)
    {
        $title = NULL;

        switch ($component) {
            case Component\Ebay::NICK:
                $title = $this->getEbayComponentHelper()->getTitle();
                break;
            case Component\Amazon::NICK:
                $title = $this->getAmazonComponentHelper()->getTitle();
                break;
        }

        return $title;
    }

    //########################################
}