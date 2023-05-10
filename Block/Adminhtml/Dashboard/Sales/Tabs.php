<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Sales;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    /** @var Tabs\Item */
    private $amountsTabItem;
    /** @var Tabs\Item */
    private $qtyTabItem;

    public function __construct(
        Tabs\Item $amountsTabItem,
        Tabs\Item $qtyTabItem,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);

        $this->amountsTabItem = $amountsTabItem;
        $this->qtyTabItem = $qtyTabItem;
    }

    public function _construct()
    {
        parent::_construct();
        $this->setDestElementId('sales_tab_container');
    }

    protected function _prepareLayout()
    {
        $this->addTab('orders', [
            'label' => __('Orders'),
            'content' => $this->qtyTabItem->toHtml(),
        ]);

        $this->addTab('amounts', [
            'label' => __('Amounts'),
            'content' => $this->amountsTabItem->toHtml(),
        ]);

        $this->setActiveTab('orders');

        return parent::_prepareLayout();
    }

    public function _toHtml()
    {
        return parent::_toHtml() . '<div id="sales_tab_container"></div>';
    }
}
