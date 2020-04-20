<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento;

/**
 * Class \Ess\M2ePro\Plugin\Order\Magento\View
 */
class View extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function aroundSetLayout(
        \Magento\Framework\View\Element\AbstractBlock $interceptor,
        \Closure $callback,
        ...$arguments
    ) {
        if (!($interceptor instanceof \Magento\Sales\Block\Adminhtml\Order\View)) {
            return $callback(...$arguments);
        }

        return $this->execute('setLayout', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processSetLayout($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\Sales\Block\Adminhtml\Order\View $interceptor */

        if ($order = $this->getOrder($interceptor)) {

            $buttonUrl = $interceptor->getUrl(
                'm2epro/'.strtolower($order->getComponentMode()).'_order/view',
                ['id' => $order->getId()]
            );

            $componentTitles = $this->getHelper('Component')->getComponentsTitles();
            $title = $componentTitles[$order->getComponentMode()];

            $interceptor->addButton(
                'go_to_m2epro_order',
                [
                    'label' => $this->getHelper('Module\Translation')->__('Show %component% Order', $title),
                    'onclick' => 'setLocation(\''.$buttonUrl.'\')',
                ],
                0,
                -1
            );
        }

        return $callback(...$arguments);
    }

    //########################################

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $interceptor
     * @return \Ess\M2ePro\Model\Order|NULL
     */
    private function getOrder($interceptor)
    {
        $magentoOrderId = $interceptor->getRequest()->getParam('order_id');
        if (empty($magentoOrderId)) {
            return null;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded(
                'Order',
                (int)$magentoOrderId,
                'magento_order_id'
            );

            if ($order === null || !$order->getId()) {
                return null;
            }

            if (!$this->getHelper('Component\\'.ucfirst($order->getComponentMode()))->isEnabled()) {
                return null;
            }

        } catch (\Exception $exception) {
            return null;
        }

        return $order;
    }

    //########################################
}
