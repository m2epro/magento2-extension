<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Item;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Item\Builder
 */
class Builder extends AbstractModel
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(array $data)
    {
        // Init general data
        // ---------------------------------------
        $this->setData('amazon_order_item_id', $data['amazon_order_item_id']);
        $this->setData('order_id', $data['order_id']);
        $this->setData('sku', trim($data['sku']));
        $this->setData('general_id', trim($data['general_id']));
        $this->setData('is_isbn_general_id', (int)$data['is_isbn_general_id']);
        $this->setData('title', trim($data['title']));
        $this->setData('gift_type', trim($data['gift_type']));
        $this->setData('gift_message', trim($data['gift_message']));
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('price', (float)$data['price']);
        $this->setData('shipping_price', (float)$data['shipping_price']);
        $this->setData('gift_price', (float)$data['gift_price']);
        $this->setData('currency', trim($data['currency']));
        $this->setData('discount_details', $this->getHelper('Data')->jsonEncode($data['discount_details']));
        $this->setData('qty_purchased', (int)$data['qty_purchased']);
        $this->setData('qty_shipped', (int)$data['qty_shipped']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($this->prepareTaxDetails($data)));
        // ---------------------------------------
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $existItem */
        $existItem = $this->amazonFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('amazon_order_item_id', $this->getData('amazon_order_item_id'))
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('sku', $this->getData('sku'))
            ->getFirstItem();

        foreach ($this->getData() as $key => $value) {
            if (!$existItem->getId() || ($existItem->hasData($key) && $existItem->getData($key) != $value)) {
                $existItem->addData($this->getData());
                $existItem->save();
                break;
            }
        }

        $amazonItem = $existItem->getChildObject();
        foreach ($this->getData() as $key => $value) {
            if (!$existItem->getId() || ($amazonItem->hasData($key) && $amazonItem->getData($key) != $value)) {
                $amazonItem->addData($this->getData());
                $amazonItem->save();
                break;
            }
        }

        return $existItem;
    }

    //########################################

    protected function prepareTaxDetails($data)
    {
        if ($this->isTaxSkippedInOrder($data)) {
            $data['tax_details']['product']['value'] = 0;
            $data['tax_details']['shipping']['value'] = 0;
            $data['tax_details']['gift']['value'] = 0;
            $data['tax_details']['total']['value'] = 0;
        }

        return $data['tax_details'];
    }

    protected function isTaxSkippedInOrder($data)
    {
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded('Order', $data['order_id']);

        foreach ($order->getChildObject()->getTaxDetails() as $tax) {
            if ($tax != 0) {
                return false;
            }
        }

        return true;
    }

    //########################################
}
