<?php

namespace Ess\M2ePro\Block\Adminhtml;

class Dashboard extends Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\ComponentTabs */
    private $componentTabs;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Sales */
    private $sales;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Products */
    private $products;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments */
    private $shipments;
    /** @var \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors */
    private $errors;
    /** @var string */
    protected $_template = 'Ess_M2ePro::dashboard.phtml';

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Dashboard\ComponentTabs $componentTabs,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Sales $sales,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Products $products,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments $shipments,
        \Ess\M2ePro\Block\Adminhtml\Dashboard\Errors $errors,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->componentTabs = $componentTabs;
        $this->sales = $sales;
        $this->products = $products;
        $this->shipments = $shipments;
        $this->errors = $errors;
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('dashboard/view.css');
        $this->setPageActionsBlock(Dashboard\PageActions::BLOCK_PATH);

        return parent::_prepareLayout();
    }

    public function getTitle(): string
    {
        return __('Dashboard');
    }

    public function getComponentTabs(): Dashboard\ComponentTabs
    {
        return $this->componentTabs;
    }

    public function getSales(): Dashboard\Sales
    {
        return $this->sales;
    }

    public function getProducts(): Dashboard\Products
    {
        return $this->products;
    }

    public function getShipments(): Dashboard\Shipments
    {
        return $this->shipments;
    }

    public function getErrors(): Dashboard\Errors
    {
        return $this->errors;
    }
}
