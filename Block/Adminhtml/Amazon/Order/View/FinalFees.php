<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\View;

class FinalFees extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $totalValue = 0;
    /** @var \Ess\M2ePro\Model\Order $order */
    private $order = null;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory */
    private $customCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonOrderViewFinalFees');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        // ---------------------------------------

        $this->order = $this->globalDataHelper->getValue('order');
    }

    protected function _prepareCollection()
    {
        $fees = $this->order->getChildObject()->getFinalFees();

        $collection = $this->customCollectionFactory->create();

        $this->totalValue = 0;

        foreach ($fees as $item) {
            $feeValue = isset($item['value']) ? (float)$item['value'] : 0;
            $this->totalValue += $feeValue;
            $collection->addItem(new \Magento\Framework\DataObject($item));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareGrid()
    {
        parent::_prepareGrid();

        $this->addTotalRow();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header' => __('Type Of Fee'),
            'align' => 'left',
            'width' => '*',
            'index' => 'title',
            'sortable' => false,
        ]);

        $this->addColumn('value', [
            'header' => __('Value'),
            'align' => 'left',
            'width' => '100px',
            'index' => 'value',
            'type' => 'number',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnValue'],
        ]);

        return parent::_prepareColumns();
    }

    protected function addTotalRow()
    {
        $row = new \Magento\Framework\DataObject([
            'title' => __('Total'),
            'value' => $this->totalValue,
        ]);

        $this->getCollection()->addItem($row);
    }

    public function callbackColumnValue($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            abs($value)
        );
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            $this->totalValue
        );
    }

    public function getRowUrl($item)
    {
        return '';
    }
}
