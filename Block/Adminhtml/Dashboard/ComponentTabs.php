<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard;

class ComponentTabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalStaticTabs
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->ebayHelper = $ebayHelper;
        $this->amazonHelper = $amazonHelper;
        $this->walmartHelper = $walmartHelper;
    }

    protected function init(): void
    {
        if ($this->ebayHelper->isEnabled()) {
            $this->addTab(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                $this->ebayHelper->getTitle(),
                $this->getUrl('*/ebay_dashboard/index')
            );
        }

        if ($this->amazonHelper->isEnabled()) {
            $this->addTab(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                $this->amazonHelper->getTitle(),
                $this->getUrl('*/amazon_dashboard/index')
            );
        }

        if ($this->walmartHelper->isEnabled()) {
            $this->addTab(
                \Ess\M2ePro\Helper\Component\Walmart::NICK,
                $this->walmartHelper->getTitle(),
                $this->getUrl('*/walmart_dashboard/index')
            );
        }

        $this->addCssForTabsContainer('float: right;');
    }

    public function setActiveComponentNick(string $nick): void
    {
        $this->setActiveTabId($nick);
    }
}
