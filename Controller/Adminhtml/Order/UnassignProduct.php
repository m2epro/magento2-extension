<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class UnassignProduct extends Order
{
    public function execute()
    {
        $orderItemId = $this->getRequest()->getPost('order_item_id');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);

        if (!$orderItem->getId()) {
            $this->setJsonContent(array(
                'error' => $this->__('Please specify Required Options.')
            ));
            return $this->getResult();
        }

        $channelOptions = $orderItem->getChildObject()->getVariationChannelOptions();

        if (!empty($channelOptions)) {
            $hash = \Ess\M2ePro\Model\Order\Matching::generateHash($channelOptions);

            $connWrite = $this->resourceConnection->getConnection();
            $connWrite->delete(
                $this->activeRecordFactory->getObject('Order\Matching')->getResource()->getMainTable(),
                array(
                    'product_id = ?' => $orderItem->getProductId(),
                    'hash = ?'       => $hash
                )
            );
        }

        $orderItem->getOrder()->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        $orderItem->unassignProduct();

        $orderItem->getOrder()->addSuccessLog(
            'Item "%title%" was successfully Unmapped.',
            array(
                'title' => $orderItem->getChildObject()->getTitle()
            )
        );

        $this->setJsonContent(array(
            'success' => $this->__('Item was successfully Unmapped.')
        ));

        return $this->getResult();
    }
}