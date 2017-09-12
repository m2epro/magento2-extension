<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Order;

class AssignProduct extends Order
{
    protected $productModel;

    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        Context $context
    )
    {
        $this->productModel = $productModel;

        parent::__construct($context);
    }

    public function execute()
    {
        $sku = $this->getRequest()->getPost('sku');
        $productId = $this->getRequest()->getPost('product_id');
        $orderItemId = $this->getRequest()->getPost('order_item_id');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);

        if ((!$productId && !$sku) || !$orderItem->getId()) {
            $this->setJsonContent(array(
                'error' => $this->__('Please specify Required Options.')
            ));
            return $this->getResult();
        }

        $collection = $this->productModel->getCollection()
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

        $productId && $collection->addFieldToFilter('entity_id', $productId);
        $sku && $collection->addFieldToFilter('sku', $sku);

        $productData = $collection->getSelect()->query()->fetch();

        if (!$productData) {
            $this->setJsonContent(array(
                'error' => $this->__('Product does not exist.')
            ));
            return $this->getResult();
        }

        $orderItem->assignProduct($productData['entity_id']);

        $orderItem->getOrder()->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $orderItem->getOrder()->addSuccessLog(
            'Order Item "%title%" was successfully Mapped.',
            array(
                'title' => $orderItem->getChildObject()->getTitle(),
            )
        );

        $this->setJsonContent(array(
            'success'  => $this->__('Order Item was successfully Mapped.'),
            'continue' => $orderItem->getMagentoProduct()->isProductWithVariations()
        ));

        return $this->getResult();
    }
}