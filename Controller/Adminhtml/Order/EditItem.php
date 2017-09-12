<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class EditItem extends Order
{
    const MAPPING_PRODUCT = 'product';
    const MAPPING_OPTIONS = 'options';

    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item_id');
        /** @var $item \Ess\M2ePro\Model\Order\Item */
        $item = $this->activeRecordFactory->getObjectLoaded('Order\Item', $itemId);

        if (is_null($item->getId())) {
            $this->setJsonContent(array(
                'error' => $this->__('Order Item does not exist.')
            ));

            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('order_item', $item);

        if (is_null($item->getProductId()) || !$item->getMagentoProduct()->exists()) {
            $block = $this->createBlock('Order\Item\Product\Mapping');

            $this->setJsonContent(array(
                'title' => $this->__('Mapping Product "%title%"', $item->getChildObject()->getTitle()),
                'html' => $block->toHtml(),
                'type' => self::MAPPING_PRODUCT,
            ));

            return $this->getResult();
        }

        if ($item->getMagentoProduct()->isProductWithVariations()) {
            $block = $this->createBlock('Order\Item\Product\Options\Mapping')->setData(array(
                'order_id' => $item->getOrderId(),
                'product_id' => $item->getProductId()
            ));

            $this->setJsonContent(array(
                'title' => $this->__('Setting Product Options'),
                'html' => $block->toHtml(),
                'type' => self::MAPPING_OPTIONS,
            ));

            return $this->getResult();
        }

        $this->setJsonContent(array(
            'error' => $this->__('Product does not have Required Options.')
        ));

        return $this->getResult();
    }
}