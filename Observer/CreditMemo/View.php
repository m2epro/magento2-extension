<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\CreditMemo;

/**
 * Class \Ess\M2ePro\Observer\CreditMemo\View
 */
class View extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $customerFactory;
    protected $registry;

    //########################################

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->customerFactory = $customerFactory;
        $this->registry = $registry;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
        $creditMemo = $this->registry->registry('current_creditmemo');
        if (empty($creditMemo) || !$creditMemo->getId()) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded(
                'Order',
                $creditMemo->getOrderId(),
                'magento_order_id'
            );
        } catch (\Exception $exception) {
            return;
        }

        if (empty($order) || !$order->getId()) {
            return;
        }

        $customerId = $creditMemo->getOrder()->getCustomerId();
        if (empty($customerId) || $creditMemo->getOrder()->getCustomerIsGuest()) {
            return;
        }

        $customer = $this->customerFactory->create()->load($customerId);

        $creditMemo->getOrder()->setData(
            'customer_'.\Ess\M2ePro\Model\Ebay\Order\ProxyObject::USER_ID_ATTRIBUTE_CODE,
            $customer->getData(\Ess\M2ePro\Model\Ebay\Order\ProxyObject::USER_ID_ATTRIBUTE_CODE)
        );
    }

    //########################################
}
