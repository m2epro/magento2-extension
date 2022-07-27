<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class AssignProduct extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    protected $magentoProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $sku = $this->getRequest()->getPost('sku');
        $productId = $this->getRequest()->getPost('product_id');
        $orderItemId = $this->getRequest()->getPost('order_item_id');

        /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);

        if ((!$productId && !$sku) || !$orderItem->getId()) {
            $this->setJsonContent([
                'error' => $this->__('Please specify Required Options.')
            ]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setStoreId($orderItem->getStoreId());
        $collection->joinStockItem();

        $productId && $collection->addFieldToFilter('entity_id', $productId);
        $sku && $collection->addFieldToFilter('sku', $sku);

        $productData = $collection->getSelect()->query()->fetch();

        if (!$productData) {
            $this->setJsonContent([
                'error' => $this->__('Product does not exist.')
            ]);
            return $this->getResult();
        }

        $orderItem->assignProduct($productData['entity_id']);

        $orderItem->getOrder()->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $orderItem->getOrder()->addSuccessLog(
            'Order Item "%title%" was Linked.',
            [
                'title' => $orderItem->getChildObject()->getTitle(),
            ]
        );

        $isPretendedToBeSimple = false;
        if ($orderItem->getMagentoProduct()->isGroupedType() &&
            $orderItem->getChildObject()->getChannelItem() !== null) {
            $isPretendedToBeSimple = $orderItem->getChildObject()->getChannelItem()->isGroupedProductModeSet();
        }

        $this->setJsonContent([
            'success'  => $this->__('Order Item was Linked.'),
            'continue' => $orderItem->getMagentoProduct()->isProductWithVariations() && !$isPretendedToBeSimple
        ]);

        return $this->getResult();
    }
}
