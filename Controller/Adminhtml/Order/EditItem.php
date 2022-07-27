<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class EditItem extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    const MAPPING_PRODUCT = 'product';
    const MAPPING_OPTIONS = 'options';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item_id');
        /** @var \Ess\M2ePro\Model\Order\Item $item */
        $item = $this->activeRecordFactory->getObjectLoaded('Order\Item', $itemId);

        if ($item->getId() === null) {
            $this->setJsonContent([
                'error' => $this->__('Order Item does not exist.')
            ]);

            return $this->getResult();
        }

        $this->globalData->setValue('order_item', $item);

        if ($item->getProductId() === null || !$item->getMagentoProduct()->exists()) {
            $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Mapping::class);

            $this->setJsonContent([
                'title' => $this->__('Linking Product "%title%"', $item->getChildObject()->getTitle()),
                'html' => $block->toHtml(),
                'type' => self::MAPPING_PRODUCT,
            ]);

            return $this->getResult();
        }

        if ($item->getMagentoProduct()->isProductWithVariations()) {
            $block = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Options\Mapping::class)
                          ->setData([
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
