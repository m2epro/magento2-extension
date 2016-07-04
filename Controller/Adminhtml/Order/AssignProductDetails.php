<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class AssignProductDetails extends Order
{
    public function execute()
    {
        $orderItemId = $this->getRequest()->getPost('order_item_id');
        $saveMatching = $this->getRequest()->getPost('save_matching');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);
        $optionsData = $this->getProductOptionsDataFromPost();

        if (count($optionsData) == 0 || !$orderItem->getId()) {
            $this->setJsonContent(array(
                'error' => $this->__('Please specify Required Options.')
            ));
            return $this->getResult();
        }

        $associatedOptions  = array();
        $associatedProducts = array();

        foreach ($optionsData as $optionId => $optionData) {
            $optionId = (int)$optionId;
            $valueId  = (int)$optionData['value_id'];

            $associatedOptions[$optionId] = $valueId;
            $associatedProducts["{$optionId}::{$valueId}"] = $optionData['product_ids'];
        }

        try {
            $orderItem->assignProductDetails($associatedOptions, $associatedProducts);
        } catch (\Exception $e) {
            $this->setJsonContent(array(
                'error' => $e->getMessage()
            ));
            return $this->getResult();
        }

        if ($saveMatching) {
            $outputData = array(
                'associated_options'  => $orderItem->getAssociatedOptions(),
                'associated_products' => $orderItem->getAssociatedProducts()
            );

            /** @var $orderMatching \Ess\M2ePro\Model\Order\Matching */
            $orderMatching = $this->activeRecordFactory->getObject('Order\Matching');
            $orderMatching->create(
                $orderItem->getProductId(),
                $orderItem->getChildObject()->getVariationChannelOptions(),
                $outputData,
                $orderItem->getComponentMode()
            );
        }

        $orderItem->getOrder()->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $orderItem->getOrder()->addSuccessLog('Order Item "%title%" Options were Successfully configured.', array(
            'title' => $orderItem->getChildObject()->getTitle()
        ));

        $this->setJsonContent(array(
            'success' => $this->__('Order Item Options were Successfully configured.')
        ));

        return $this->getResult();
    }
}