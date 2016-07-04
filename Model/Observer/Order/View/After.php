<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Order\View;

class After extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function execute(\Magento\Framework\Event\Observer $eventObserver)
    {
        // event dispatched for ALL rendered magento blocks, so we need to skip unnecessary blocks ASAP
        if (!($eventObserver->getEvent()->getBlock() instanceof \Magento\Sales\Block\Adminhtml\Order\View)) {
            return;
        }

        parent::execute($eventObserver);
    }

    public function process()
    {
        /** @var \Magento\Sales\Block\Adminhtml\Order\View $block */
        $block = $this->getEvent()->getBlock();

        $magentoOrderId = $block->getRequest()->getParam('order_id');
        if (empty($magentoOrderId)) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded(
                'Order', (int)$magentoOrderId, 'magento_order_id'
            );
        } catch (\Exception $exception) {
            return;
        }

        if (is_null($order) || !$order->getId()) {
            return;
        }

        if (!$this->getHelper('Component\\'.ucfirst($order->getComponentMode()))->isEnabled()) {
            return;
        }

        if ($order->isComponentModeEbay()) {
            $buttonUrl = $block->getUrl('M2ePro/ebay_order/view', array('id' => $order->getId()));
        } else {
            $buttonUrl = $block->getUrl(
                'M2ePro/'.strtolower($order->getComponentMode()).'_order/view',
                array('id' => $order->getId())
            );
        }

        $componentTitles = $this->getHelper('Component')->getComponentsTitles();
        $title = $componentTitles[$order->getComponentMode()];

        $block->addButton(
            'go_to_m2epro_order',
            array(
                'label' => $this->getHelper('Module\Translation')->__('Show %component% Order', $title),
                'onclick' => 'setLocation(\''.$buttonUrl.'\')',
            ),
            0, -1
        );
    }

    //########################################
}