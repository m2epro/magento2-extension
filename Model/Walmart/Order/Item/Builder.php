<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Item;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Item\Builder
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    private $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(array $data)
    {
        // Init general data
        // ---------------------------------------
        $this->setData('walmart_order_item_id', $data['walmart_order_item_id']);
        $this->setData('status', $data['status']);
        $this->setData('order_id', $data['order_id']);
        $this->setData('sku', trim($data['sku']));
        $this->setData('title', trim($data['title']));
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('price', (float)$data['price']);
        $this->setData('qty', (int)$data['qty']);
        // ---------------------------------------

        /**
         * Walmart returns the same Order Item more than one time with single QTY. We will merge this data
         */
        // ---------------------------------------
        if (!empty($data['merged_walmart_order_item_ids'])) {
            $this->setData(
                'merged_walmart_order_item_ids',
                $this->getHelper('Data')->jsonEncode($data['merged_walmart_order_item_ids'])
            );
        }
        // ---------------------------------------
    }

    //########################################

    public function process()
    {
        return $this->createOrderItem();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item
     */
    private function createOrderItem()
    {
        $existItem = $this->walmartFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('walmart_order_item_id', $this->getData('walmart_order_item_id'))
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('sku', $this->getData('sku'))
            ->getFirstItem();

        $existItem->addData($this->getData());
        $existItem->save();

        $existItem->getChildObject()->addData($this->getData());
        $existItem->getChildObject()->save();

        return $existItem;
    }

    //########################################
}
