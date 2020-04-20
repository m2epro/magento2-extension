<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\UnassignProduct
 */
class UnassignProduct extends Order
{
    public function execute()
    {
        $orderItemId = $this->getRequest()->getPost('order_item_id');

        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $orderItemId);

        if (!$orderItem->getId()) {
            $this->setJsonContent([
                'error' => $this->__('Please specify Required Options.')
            ]);
            return $this->getResult();
        }

        $channelOptions = $orderItem->getChildObject()->getVariationChannelOptions();

        if (!empty($channelOptions)) {
            $hash = \Ess\M2ePro\Model\Order\Matching::generateHash($channelOptions);

            $connWrite = $this->resourceConnection->getConnection();
            $connWrite->delete(
                $this->activeRecordFactory->getObject('Order\Matching')->getResource()->getMainTable(),
                [
                    'product_id = ?' => $orderItem->getProductId(),
                    'hash = ?'       => $hash
                ]
            );
        }

        $orderItem->getOrder()->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        $orderItem->unassignProduct();

        $orderItem->getOrder()->addSuccessLog(
            'Item "%title%" was successfully Unmapped.',
            [
                'title' => $orderItem->getChildObject()->getTitle()
            ]
        );

        $this->setJsonContent([
            'success' => $this->__('Item was successfully Unmapped.')
        ]);

        return $this->getResult();
    }
}
