<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Dashboard;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\DashboardFactory */
    private $dashboardFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Dashboard\Sales\CachedCalculator */
    private $salesCalculator;
    /** @var \Ess\M2ePro\Model\Ebay\Dashboard\Products\CachedCalculator */
    private $productsCalculator;
    /** @var \Ess\M2ePro\Model\Ebay\Dashboard\Shipments\CachedCalculator */
    private $shipmentsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Dashboard\Shipments\UrlStorage */
    private $shipmentsUrlStorage;
    /** @var \Ess\M2ePro\Model\Ebay\Dashboard\Errors\CachedCalculator */
    private $errorsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Dashboard\Errors\UrlStorage */
    private $errorsUrlStorage;
    /** @var \Ess\M2ePro\Model\Dashboard\ListingProductIssues\CalculatorInterface */
    private $listingProductsIssuesCalculator;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\DashboardFactory $dashboardFactory,
        \Ess\M2ePro\Model\Ebay\Dashboard\Sales\CachedCalculator $salesCalculator,
        \Ess\M2ePro\Model\Ebay\Dashboard\Products\CachedCalculator $productsCalculator,
        \Ess\M2ePro\Model\Ebay\Dashboard\Shipments\CachedCalculator $shipmentsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Ebay\Dashboard\Shipments\UrlStorage $shipmentsUrlStorage,
        \Ess\M2ePro\Model\Ebay\Dashboard\Errors\CachedCalculator $errorsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Ebay\Dashboard\Errors\UrlStorage $errorsUrlStorage,
        \Ess\M2ePro\Model\Ebay\Dashboard\ListingProductIssues\CachedCalculator $listingProductsIssuesCalculator,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->dashboardFactory = $dashboardFactory;
        $this->salesCalculator = $salesCalculator;
        $this->productsCalculator = $productsCalculator;
        $this->shipmentsCalculator = $shipmentsCalculator;
        $this->shipmentsUrlStorage = $shipmentsUrlStorage;
        $this->errorsCalculator = $errorsCalculator;
        $this->errorsUrlStorage = $errorsUrlStorage;
        $this->listingProductsIssuesCalculator = $listingProductsIssuesCalculator;
    }

    public function execute()
    {
        $block = $this->dashboardFactory->create(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            $this->getLayout(),
            $this->salesCalculator,
            $this->productsCalculator,
            $this->shipmentsCalculator,
            $this->shipmentsUrlStorage,
            $this->errorsCalculator,
            $this->errorsUrlStorage,
            $this->listingProductsIssuesCalculator
        );

        $this->addContent($block);
        $this->getResultPage()->getConfig()->getTitle()->prepend($block->getTitle());

        return $this->getResult();
    }
}
