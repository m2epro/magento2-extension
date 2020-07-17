<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon;

use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order
 */
class Order extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $orderId = (int)$row->getId();

        // Prepare collection
        // ---------------------------------------
        $orderLogsCollection = $this->activeRecordFactory->getObject('Order\Log')->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->setOrder('id', 'DESC');
        $orderLogsCollection->getSelect()
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::ACTIONS_COUNT);

        if (!$orderLogsCollection->getSize()) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Order_Log_Grid_LastActions')->setData([
            'entity_id' => $orderId,
            'logs'      => $orderLogsCollection->getItems(),
            'view_help_handler' => 'OrderObj.viewOrderHelp',
            'hide_help_handler' => 'OrderObj.hideOrderHelp',
        ]);

        return $summary->toHtml();
    }

    //########################################
}
