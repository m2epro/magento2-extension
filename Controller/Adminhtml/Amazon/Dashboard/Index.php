<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Dashboard;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\DashboardFactory */
    private $dashboardFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Sales\CachedCalculator */
    private $salesCalculator;
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Products\CachedCalculator */
    private $productsCalculator;
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Shipments\CachedCalculator */
    private $shipmentsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Dashboard\Shipments\UrlStorage */
    private $shipmentsUrlStorage;
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\Errors\CachedCalculator */
    private $errorsCalculator;
    /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Dashboard\Errors\UrlStorage */
    private $errorsUrlStorage;
    /** @var \Ess\M2ePro\Model\Amazon\Dashboard\ListingProductIssues\CachedCalculator */
    private $listingProductsIssuesCalculator;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\DashboardFactory $dashboardFactory,
        \Ess\M2ePro\Model\Amazon\Dashboard\Sales\CachedCalculator $salesCalculator,
        \Ess\M2ePro\Model\Amazon\Dashboard\Products\CachedCalculator $productsCalculator,
        \Ess\M2ePro\Model\Amazon\Dashboard\Shipments\CachedCalculator $shipmentsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Amazon\Dashboard\Shipments\UrlStorage $shipmentsUrlStorage,
        \Ess\M2ePro\Model\Amazon\Dashboard\Errors\CachedCalculator $errorsCalculator,
        \Ess\M2ePro\Block\Adminhtml\Amazon\Dashboard\Errors\UrlStorage $errorsUrlStorage,
        \Ess\M2ePro\Model\Amazon\Dashboard\ListingProductIssues\CachedCalculator $listingProductsIssuesCalculator,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

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
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
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
