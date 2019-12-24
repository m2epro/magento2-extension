<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\CheckProductOptionStockAvailability
 */
class CheckProductOptionStockAvailability extends Order
{
    public function execute()
    {
        $orderItemId = $this->getRequest()->getParam('order_item_id');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);
        $optionsData = $this->getProductOptionsDataFromPost();

        if (count($optionsData) == 0 || !$orderItem->getId()) {
            $this->setJsonContent(['is_in_stock' => false]);
            return $this->getResult();
        }

        $associatedProducts = [];

        foreach ($optionsData as $optionId => $optionData) {
            $optionId = (int)$optionId;
            $valueId  = (int)$optionData['value_id'];

            $associatedProducts["{$optionId}::{$valueId}"] = $optionData['product_ids'];
        }

        $associatedProducts = $this->getHelper('Magento\Product')->prepareAssociatedProducts(
            $associatedProducts,
            $orderItem->getMagentoProduct()
        );

        foreach ($associatedProducts as $productId) {
            $magentoProductTemp = $this->modelFactory->getObject('Magento\Product');
            $magentoProductTemp->setProductId($productId);

            if (!$magentoProductTemp->isStockAvailability()) {
                $this->setJsonContent(['is_in_stock' => false]);
                return $this->getResult();
            }
        }

        $this->setJsonContent(['is_in_stock' => true]);

        return $this->getResult();
    }
}
