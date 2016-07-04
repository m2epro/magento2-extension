<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class CheckProductOptionStockAvailability extends Order
{
    public function execute()
    {
        $orderItemId = $this->getRequest()->getParam('order_item_id');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);
        $optionsData = $this->getProductOptionsDataFromPost();

        if (count($optionsData) == 0 || !$orderItem->getId()) {
            $this->setJsonContent(array('is_in_stock' => false));
            return $this->getResult();
        }

        $associatedProducts = array();

        foreach ($optionsData as $optionId => $optionData) {
            $optionId = (int)$optionId;
            $valueId  = (int)$optionData['value_id'];

            $associatedProducts["{$optionId}::{$valueId}"] = $optionData['product_ids'];
        }

        /** @var $optionsFinder \Ess\M2ePro\Model\Order\Item\OptionsFinder */
        $optionsFinder = $this->modelFactory->getObject('Order\Item\OptionsFinder');
        $optionsFinder->setProductId($orderItem->getMagentoProduct()->getProductId());
        $optionsFinder->setProductType($orderItem->getMagentoProduct()->getTypeId());

        $associatedProducts = $optionsFinder->prepareAssociatedProducts($associatedProducts);

        foreach ($associatedProducts as $productId) {

            $magentoProductTemp = $this->modelFactory->getObject('Magento\Product');
            $magentoProductTemp->setProductId($productId);

            if (!$magentoProductTemp->isStockAvailability()) {
                $this->setJsonContent(array('is_in_stock' => false));
                return $this->getResult();
            }
        }

        $this->setJsonContent(array('is_in_stock' => true));

        return $this->getResult();
    }
}