<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Dashboard;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\DashboardFactory */
    private $dashboardFactory;
    /** @var \Ess\M2ePro\Model\Walmart\Dashboard\Sales\CachedCalculator */
    private $salesCalculator;
    /** @var \Ess\M2ePro\Model\Walmart\Dashboard\Products\CachedCalculator */
    private $productsCalculator;
    /** @var \Ess\M2ePro\Model\Walmart\Dashboard\Shipments\Calculator */
    private $shipmentsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Shipments\UrlStorage */
    private $shipmentsUrlStorage;
    /** @var \Ess\M2ePro\Model\Walmart\Dashboard\Errors\Calculator */
    private $errorsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Errors\UrlStorage */
    private $errorsUrlStorage;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\DashboardFactory $dashboardFactory,
        \Ess\M2ePro\Model\Walmart\Dashboard\Sales\CachedCalculator $salesCalculator,
        \Ess\M2ePro\Model\Walmart\Dashboard\Products\CachedCalculator $productsCalculator,
        \Ess\M2ePro\Model\Walmart\Dashboard\Shipments\CachedCalculator $shipmentsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Shipments\UrlStorage $shipmentsUrlStorage,
        \Ess\M2ePro\Model\Walmart\Dashboard\Errors\CachedCalculator $errorsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Walmart\Dashboard\Errors\UrlStorage $errorsUrlStorage,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->dashboardFactory = $dashboardFactory;
        $this->salesCalculator = $salesCalculator;
        $this->productsCalculator = $productsCalculator;
        $this->shipmentsCalculator = $shipmentsCalculator;
        $this->errorsCalculator = $errorsCalculator;
        $this->shipmentsUrlStorage = $shipmentsUrlStorage;
        $this->errorsUrlStorage = $errorsUrlStorage;
    }

    public function execute()
    {
        $block = $this->dashboardFactory->create(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            $this->getLayout(),
            $this->salesCalculator,
            $this->productsCalculator,
            $this->shipmentsCalculator,
            $this->shipmentsUrlStorage,
            $this->errorsCalculator,
            $this->errorsUrlStorage
        );

        $this->addContent($block);
        $this->getResultPage()->getConfig()->getTitle()->prepend($block->getTitle());

        return $this->getResult();
    }
}
