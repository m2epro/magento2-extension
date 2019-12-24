<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\EditItem
 */
class EditItem extends Order
{
    const MAPPING_PRODUCT = 'product';
    const MAPPING_OPTIONS = 'options';

    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item_id');
        /** @var $item \Ess\M2ePro\Model\Order\Item */
        $item = $this->activeRecordFactory->getObjectLoaded('Order\Item', $itemId);

        if ($item->getId() === null) {
            $this->setJsonContent([
                'error' => $this->__('Order Item does not exist.')
            ]);

            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('order_item', $item);

        if ($item->getProductId() === null || !$item->getMagentoProduct()->exists()) {
            $block = $this->createBlock('Order_Item_Product_Mapping');

            $this->setJsonContent([
                'title' => $this->__('Mapping Product "%title%"', $item->getChildObject()->getTitle()),
                'html' => $block->toHtml(),
                'type' => self::MAPPING_PRODUCT,
            ]);

            return $this->getResult();
        }

        if ($item->getMagentoProduct()->isProductWithVariations()) {
            $block = $this->createBlock('Order_Item_Product_Options_Mapping')->setData([
                'order_id' => $item->getOrderId(),
                'product_id' => $item->getProductId()
            ]);

            $this->setJsonContent([
                'title' => $this->__('Setting Product Options'),
                'html' => $block->toHtml(),
                'type' => self::MAPPING_OPTIONS,
            ]);

            return $this->getResult();
        }

        $this->setJsonContent([
            'error' => $this->__('Product does not have Required Options.')
        ]);

        return $this->getResult();
    }
}
