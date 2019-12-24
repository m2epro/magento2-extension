<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order\View\ExternalTransaction
 */
class ExternalTransaction extends AbstractGrid
{
    /** @var $order \Ess\M2ePro\Model\Order */
    private $order = null;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrderViewExternalTransaction');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        // ---------------------------------------

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');
    }

    protected function _prepareCollection()
    {
        $collection = $this->order->getChildObject()->getExternalTransactionsCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('transaction_id', [
            'header' => $this->__('Transaction #'),
            'align' => 'left',
            'width' => '*',
            'index' => 'transaction_id',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnTransactionId']
        ]);

        $this->addColumn('fee', [
            'header' => $this->__('Fee'),
            'align' => 'left',
            'width' => '100px',
            'index' => 'fee',
            'type' => 'number',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnFee']
        ]);

        $this->addColumn('sum', [
            'header' => $this->__('Amount'),
            'align' => 'left',
            'width' => '100px',
            'index' => 'sum',
            'type' => 'number',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnSum']
        ]);

        $this->addColumn('transaction_date', [
            'header'   => $this->__('Date'),
            'align'    => 'left',
            'width'    => '150px',
            'index'    => 'transaction_date',
            'type'     => 'datetime',
            'filter'   => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'format'   => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'sortable' => false
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnTransactionId($value, $row, $column, $isExport)
    {
        if (strtolower($this->order->getChildObject()->getPaymentMethod()) != 'paypal') {
            return $value;
        }

        $url = $this->getUrl('*/*/goToPaypal', ['transaction_id' => $value]);

        return '<a href="'.$url.'" target="_blank">'.$value.'</a>';
    }

    public function callbackColumnFee($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            $value
        );
    }

    public function callbackColumnSum($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            $value
        );
    }

    public function getRowUrl($row)
    {
        return '';
    }

    //########################################
}
