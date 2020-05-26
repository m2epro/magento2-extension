<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\SellerOrderId
 */
class SellerOrderId extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/update/seller_order_id';

    const ORDERS_PER_MERCHANT = 1000;

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    protected $orderResourceFactory;

    //####################################

    public function __construct(
        \Magento\Sales\Model\ResourceModel\OrderFactory $orderResourceFactory,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
    ) {
        $this->orderResourceFactory = $orderResourceFactory;
        parent::__construct(
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //####################################

    protected function performActions()
    {
        /** @var $accounts \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Collection */
        $accounts = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        // Getting accounts with enabled feature
        $enabledAccountIds = [];
        $enabledMerchantIds = [];

        foreach ($accounts->getItems() as $account) {
            /** @var $account \Ess\M2ePro\Model\Account */

            if ($account->getChildObject()->isMagentoOrdersNumberApplyToAmazonOrderEnable()) {
                $enabledAccountIds[] = $account->getId();
                $enabledMerchantIds[] = $account->getChildObject()->getMerchantId();
            }
        }

        if (empty($enabledAccountIds)) {
            return;
        }

        // Processing orders from last day
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('-1 day');

        $amazonOrderTable = $this->activeRecordFactory->getObject('Amazon\Order')->getResource()->getMainTable();
        $connection = $this->resource->getConnection();

        $enabledMerchantIds = array_unique($enabledMerchantIds);

        foreach ($enabledMerchantIds as $enabledMerchantId) {
            /** @var $ordersCollection \Ess\M2ePro\Model\ResourceModel\Order\Collection */
            $ordersCollection = $this->parentFactory->getObject(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Order'
            )->getCollection();

            $ordersCollection->addFieldToFilter('main_table.account_id', ['in' => $enabledAccountIds]);
            $ordersCollection->addFieldToFilter('main_table.magento_order_id', ['notnull' => true]);
            $ordersCollection->addFieldToFilter(
                'main_table.create_date',
                ['gt' => $backToDate->format('Y-m-d H:i:s')]
            );
            $ordersCollection->addFieldToFilter(
                'second_table.status',
                ['neq' => \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED]
            );
            $ordersCollection->addFieldToFilter('second_table.seller_order_id', ['null' => true]);

            $ordersCollection->getSelect()->join(
                ['sfo' => $this->orderResourceFactory->create()->getMainTable()],
                '(`main_table`.`magento_order_id` = `sfo`.`entity_id`)',
                [
                    'increment_id' => 'sfo.increment_id',
                ]
            );

            $ordersCollection->getSelect()->join(
                ['maa' => $this->activeRecordFactory->getObject('Amazon\Account')->getResource()->getMainTable()],
                '(`main_table`.`account_id` = `maa`.`account_id`)',
                [
                    'merchant_id' => 'maa.merchant_id',
                    'server_hash' => 'maa.server_hash',
                ]
            );

            $ordersCollection->addFieldToFilter('maa.merchant_id', ['eq' => $enabledMerchantId]);

            $ordersCollection->getSelect()->limit(self::ORDERS_PER_MERCHANT);

            // Preparing data structure for calls
            $orders = [];
            $accounts = [];
            $ordersToUpdate = [];

            foreach ($ordersCollection->getData() as $orderData) {
                $orders[$orderData['order_id']] = [
                    'amazon_order_id' => $orderData['amazon_order_id'],
                    'seller_order_id' => $orderData['increment_id']
                ];
                $accounts[] = $orderData['server_hash'];

                $ordersToUpdate[$orderData['order_id']] = [
                    'seller_order_id' => $orderData['increment_id']
                ];
            }

            if (empty($ordersToUpdate)) {
                continue;
            }

            // Sending update requests
            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'orders',
                'update',
                'sellerOrderId',
                [
                    'orders' => $orders,
                    'accounts' => array_unique($accounts),
                    'ignore_processing_request' => 1
                ]
            );
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            // Updating orders if Amazon accepted processing
            if (isset($response['processed']) && $response['processed'] == true) {
                /** @var \Ess\M2ePro\Model\Order\Log $logModel */
                $logModel = $this->activeRecordFactory->getObject('Order\Log');
                $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

                foreach ($ordersToUpdate as $orderId => $orderData) {
                    $connection->update(
                        $amazonOrderTable,
                        [
                            'seller_order_id' => $orderData['seller_order_id']
                        ],
                        '`order_id` = ' . $orderId
                    );

                    $logModel->addMessage(
                        $orderId,
                        $this->getHelper('Module\Translation')->__(
                            'Magento Order number has been set as Your Seller Order ID in Amazon Order details.'
                        ),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
                    );
                }
            }
        }
    }

    //####################################
}
