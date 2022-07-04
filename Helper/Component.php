<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Component
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    /**
     * @param \Ess\M2ePro\Helper\Component\Ebay $ebayHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $amazonHelper
     * @param \Ess\M2ePro\Helper\Component\Walmart $walmartHelper
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper
    ) {
        $this->ebayHelper = $ebayHelper;
        $this->amazonHelper = $amazonHelper;
        $this->walmartHelper = $walmartHelper;
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Helper\Component\Ebay
     */
    public function getEbayComponentHelper(): Component\Ebay
    {
        return $this->ebayHelper;
    }

    /**
     * @return \Ess\M2ePro\Helper\Component\Amazon
     */
    public function getAmazonComponentHelper(): Component\Amazon
    {
        return $this->amazonHelper;
    }

    /**
     * @return \Ess\M2ePro\Helper\Component\Walmart
     */
    public function getWalmartComponentHelper(): Component\Walmart
    {
        return $this->walmartHelper;
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getComponents(): array
    {
        return [
            Component\Ebay::NICK,
            Component\Amazon::NICK,
            Component\Walmart::NICK,
        ];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getComponentsTitles(): array
    {
        return [
            Component\Ebay::NICK    => $this->ebayHelper->getTitle(),
            Component\Amazon::NICK  => $this->amazonHelper->getTitle(),
            Component\Walmart::NICK => $this->walmartHelper->getTitle(),
        ];
    }

    // ----------------------------------------

    /**
     * @return string[]
     */
    public function getEnabledComponents(): array
    {
        $components = [];

        if ($this->ebayHelper->isEnabled()) {
            $components[] = Component\Ebay::NICK;
        }
        if ($this->amazonHelper->isEnabled()) {
            $components[] = Component\Amazon::NICK;
        }
        if ($this->walmartHelper->isEnabled()) {
            $components[] = Component\Walmart::NICK;
        }

        return $components;
    }

    /**
     * @param $viewNick
     *
     * @return string|void
     */
    public function getEnabledComponentByView($viewNick)
    {
        $enabledComponents = $this->getEnabledComponents();

        if (
            $viewNick === \Ess\M2ePro\Helper\View\Ebay::NICK &&
            in_array(Component\Ebay::NICK, $enabledComponents)
        ) {
            return Component\Ebay::NICK;
        }

        if (
            $viewNick === \Ess\M2ePro\Helper\View\Amazon::NICK &&
            in_array(Component\Amazon::NICK, $enabledComponents)
        ) {
            return Component\Amazon::NICK;
        }

        if (
            $viewNick === \Ess\M2ePro\Helper\View\Walmart::NICK &&
            in_array(Component\Walmart::NICK, $enabledComponents)
        ) {
            return Component\Walmart::NICK;
        }
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getEnabledComponentsTitles(): array
    {
        $components = [];

        if ($this->ebayHelper->isEnabled()) {
            $components[Component\Ebay::NICK] = $this->ebayHelper->getTitle();
        }
        if ($this->amazonHelper->isEnabled()) {
            $components[Component\Amazon::NICK] = $this->amazonHelper->getTitle();
        }
        if ($this->walmartHelper->isEnabled()) {
            $components[Component\Walmart::NICK] = $this->walmartHelper->getTitle();
        }

        return $components;
    }

    /**
     * @return array
     */
    public function getDisabledComponents(): array
    {
        $components = [];

        if (!$this->ebayHelper->isEnabled()) {
            $components[] = Component\Ebay::NICK;
        }
        if (!$this->amazonHelper->isEnabled()) {
            $components[] = Component\Amazon::NICK;
        }
        if (!$this->walmartHelper->isEnabled()) {
            $components[] = Component\Walmart::NICK;
        }

        return $components;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDisabledComponentsTitles(): array
    {
        $components = [];

        if (!$this->ebayHelper->isEnabled()) {
            $components[Component\Ebay::NICK] = $this->ebayHelper->getTitle();
        }
        if (!$this->amazonHelper->isEnabled()) {
            $components[Component\Amazon::NICK] = $this->amazonHelper->getTitle();
        }
        if (!$this->walmartHelper->isEnabled()) {
            $components[Component\Walmart::NICK] = $this->walmartHelper->getTitle();
        }

        return $components;
    }

    // ----------------------------------------

    /**
     * @param string $component
     *
     * @return string|null
     */
    public function getComponentTitle(string $component): ?string
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
}
