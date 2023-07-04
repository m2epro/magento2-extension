<?php

namespace Ess\M2ePro\Model\Ebay\Api\DataSources;

use Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterface;
use Ess\M2ePro\Api\Ebay\Data\Order\OrderItemInterface as OrderItem;
use Ess\M2ePro\Api\Ebay\Data\OrderInterface;

class Orders implements \Ess\M2ePro\Api\Ebay\DataSources\DataSourceInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\Data\OrderInterfaceFactory */
    private $orderFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order */
    private $orderResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order */
    private $ebayOrderResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item */
    private $orderItemResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order\Item */
    private $ebayOrderItemResource;

    public function __construct(
        \Ess\M2ePro\Api\Ebay\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Order $orderResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order $ebayOrderResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Order\Item $orderItemResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order\Item $ebayOrderItemResource
    ) {
        $this->orderFactory = $orderFactory;
        $this->resourceConnection = $resourceConnection;
        $this->orderResource = $orderResource;
        $this->ebayOrderResource = $ebayOrderResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->orderItemResource = $orderItemResource;
        $this->ebayOrderItemResource = $ebayOrderItemResource;
    }

    /**
     * @inheritDoc
     */
    public function findByCriteria(
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria,
        \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface $searchResult
    ): \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface {
        $select = $this->initSelectQuery();
        $select = $this->applyFilters($select, $searchCriteria);
        $select = $this->applyPagination($select, $searchCriteria);

        $pageData = $this->fetchAll($select);

        $searchResult->setItems($this->prepareItems($pageData));
        $searchResult->setTotalCount($this->getTotalCount($select));
        $searchResult->setPageSize($searchCriteria->getPageSize());
        $searchResult->setPage($searchCriteria->getPage());

        return $searchResult;
    }

    /**
     * @inheirtDoc
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    public function findOne(int $id): OrderInterface
    {
        $select = $this->initSelectQuery();
        $select->where('o.id = ?', $id);
        $select->limit(1);

        $orderItem = $this->fetchOne($select);

        if (empty($orderItem)) {
            throw new \Ess\M2ePro\Api\Exception\NotFoundException('Order not found', [
                'id' => $id,
            ]);
        }

        return $this->prepareOrderItem($orderItem);
    }

    private function initSelectQuery(): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection->getConnection()->select();

        $buyerJsonFields = $this->makeSqlJsonObjectFields([
            BuyerInterface::NAME_KEY => 'eo.buyer_name',
            BuyerInterface::EMAIL_KEY => 'eo.buyer_email',
            BuyerInterface::USER_ID_KEY => 'eo.buyer_user_id',
            BuyerInterface::MESSAGE_KEY => 'eo.buyer_message',
            BuyerInterface::TAX_ID_KEY => 'eo.buyer_tax_id'
        ]);

        $select->from(['eo' => $this->ebayOrderResource->getMainTable()], [
            OrderInterface::EBAY_ORDER_ID_KEY => 'eo.ebay_order_id',
            OrderInterface::SELLING_MANAGER_ID_KEY => 'eo.selling_manager_id',
            OrderInterface::BUYER_KEY
                => new \Zend_Db_Expr('JSON_OBJECT(' . $buyerJsonFields . ')'),
            OrderInterface::PAID_AMOUNT_KEY => 'eo.paid_amount',
            OrderInterface::SAVED_AMOUNT_KEY => 'eo.saved_amount',
            OrderInterface::FINAL_FEE_KEY => 'eo.final_fee',
            OrderInterface::CURRENCY_KEY => 'eo.currency',
            OrderInterface::SHIPPING_DETAILS_KEY => 'eo.shipping_details',
            OrderInterface::PAYMENT_DETAILS_KEY => 'eo.payment_details',
            OrderInterface::TAX_DETAILS_KEY => 'eo.tax_details',
            OrderInterface::TAX_REFERENCE_KEY => 'eo.tax_reference',
            OrderInterface::SHIPPING_DATE_TO_KEY => 'eo.shipping_date_to',
            OrderInterface::PURCHASE_CREATE_DATE_KEY => 'eo.purchase_create_date',
            OrderInterface::PURCHASE_UPDATE_DATE_KEY => 'eo.purchase_update_date',
        ]);

        $select = $this->joinAccountTable($select);
        $select = $this->joinMarketplaceTable($select);
        $select = $this->joinOrderItemTable($select);

        $select->order('o.id');

        return $select;
    }

    private function joinMarketplaceTable(\Magento\Framework\DB\Select $select): \Magento\Framework\DB\Select
    {
        $select->joinLeft(
            ['m' => $this->marketplaceResource->getMainTable()],
            'o.marketplace_id = m.id',
            [
                OrderInterface::MARKETPLACE_CODE_KEY => 'm.code'
            ]
        );

        return $select;
    }

    private function joinAccountTable(\Magento\Framework\DB\Select $select): \Magento\Framework\DB\Select
    {
        $select->joinLeft(
            ['o' => $this->orderResource->getMainTable()],
            'eo.order_id = o.id',
            [
                OrderInterface::ID_KEY => 'o.id',
                OrderInterface::ACCOUNT_ID_KEY => 'o.account_id',
                OrderInterface::CREATE_DATE_KEY => 'o.create_date',
                OrderInterface::UPDATE_DATE_KEY => 'o.update_date',
            ]
        );

        return $select;
    }

    private function joinOrderItemTable(\Magento\Framework\DB\Select $select): \Magento\Framework\DB\Select
    {
        $select->join(
            ['oi' => $this->orderItemResource->getMainTable()],
            'oi.order_id = o.id',
            []
        );
        $select->join(
            ['eoi' => $this->ebayOrderItemResource->getMainTable()],
            'eoi.order_item_id = oi.id',
            []
        );
        $select->group('o.id');

        $orderItemsColumns = $this->makeSqlJsonObjectFields([
            OrderItem::ID_KEY => 'oi.id',
            OrderItem::MAGENTO_PRODUCT_ID_KEY => 'oi.product_id',
            OrderItem::CREATE_DATE_KEY => 'oi.create_date',
            OrderItem::UPDATE_DATE_KEY => 'oi.update_date',
            OrderItem::TRANSACTION_ID_KEY => 'eoi.transaction_id',
            OrderItem::SELLING_MANAGER_ID_KEY => 'eoi.selling_manager_id',
            OrderItem::EBAY_ITEM_ID_KEY => 'eoi.item_id',
            OrderItem::TITLE_KEY => 'eoi.title',
            OrderItem::SKU_KEY => 'eoi.sku',
            OrderItem::PRICE_KEY => 'eoi.price',
            OrderItem::TAX_DETAILS_KEY => 'eoi.tax_details',
            OrderItem::TRACKING_DETAILS_KEY => 'eoi.tracking_details',
            OrderItem::FINAL_FEE_KEY => 'eoi.final_fee',
            OrderItem::WASTE_RECYCLING_FEE_KEY => 'eoi.waste_recycling_fee',
        ]);

        $select->columns([
            'order_items' => new \Zend_Db_Expr("CONCAT('[', GROUP_CONCAT(JSON_OBJECT($orderItemsColumns)), ']')"),
        ]);

        return $select;
    }

    private function applyFilters(
        \Magento\Framework\DB\Select $select,
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
    ): \Magento\Framework\DB\Select {
        if ($searchCriteria->getMarketplaceCode() !== null) {
            $select->where('m.code = ?', $searchCriteria->getMarketplaceCode());
        }

        if ($searchCriteria->getAccountId() !== null) {
            $select->where('o.account_id = ?', $searchCriteria->getAccountId());
        }

        return $select;
    }

    private function applyPagination(
        \Magento\Framework\DB\Select $select,
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
    ): \Magento\Framework\DB\Select {
        $limit = $searchCriteria->getPageSize();
        $offset = ($searchCriteria->getPage() - 1) * $limit;

        $select->limit($limit, $offset);

        return $select;
    }

    private function fetchAll(\Magento\Framework\DB\Select $select): array
    {
        return $this->resourceConnection
            ->getConnection()
            ->fetchAll($select);
    }

    private function fetchOne(\Magento\Framework\DB\Select $select): array
    {
        return $this->resourceConnection
            ->getConnection()
            ->fetchRow($select) ?: [];
    }

    private function getTotalCount(\Magento\Framework\DB\Select $select): int
    {
        $select = clone $select;
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->reset(\Magento\Framework\DB\Select::DISTINCT);
        $select->reset(\Magento\Framework\DB\Select::HAVING);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->columns(new \Zend_Db_Expr('1'));

        $countSelect = $this->resourceConnection->getConnection()->select();
        $countSelect->from(['t' => $select], [new \Zend_Db_Expr('COUNT(*)')]);

        return (int)$this->resourceConnection
            ->getConnection()
            ->fetchOne($countSelect);
    }

    /**
     * @param array $orderItems
     *
     * @return OrderInterface[]
     */
    private function prepareItems(array $orderItems): array
    {
        $items = [];
        foreach ($orderItems as $orderItem) {
            $items[] = $this->prepareOrderItem($orderItem);
        }

        return $items;
    }

    private function prepareOrderItem(array $data): OrderInterface
    {
        $order = $this->orderFactory->create();
        $order->addData($data);

        return $order;
    }

    private function makeSqlJsonObjectFields(array $fields): string
    {
        $result = '';
        foreach ($fields as $alias => $dbField) {
            $result .= sprintf("'%s', %s,", $alias, $dbField);
        }

        return rtrim($result, ',');
    }
}
