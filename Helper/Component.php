<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Component extends AbstractHelper
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    protected $ebayHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    protected $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        parent::__construct($helperFactory, $context);

        $this->ebayHelper = $ebayHelper;
        $this->amazonHelper = $amazonHelper;
        $this->walmartHelper = $walmartHelper;
    }

    //########################################

    public function getEbayComponentHelper()
    {
        return $this->ebayHelper;
    }

    public function getAmazonComponentHelper()
    {
        return $this->amazonHelper;
    }

    public function getWalmartComponentHelper()
    {
        return $this->walmartHelper;
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
            Component\Ebay::NICK    => $this->ebayHelper->getTitle(),
            Component\Amazon::NICK  => $this->amazonHelper->getTitle(),
            Component\Walmart::NICK => $this->walmartHelper->getTitle(),
        ];
    }

    //########################################

    public function getEnabledComponents()
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

    //########################################

    public function getDisabledComponents()
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

    public function getDisabledComponentsTitles()
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
