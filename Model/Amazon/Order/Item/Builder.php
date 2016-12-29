<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Item;

use Ess\M2ePro\Model\AbstractModel;

class Builder extends AbstractModel
{
    private $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        array $data = []
    )
    {
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
        $this->setData('gift_price', (float)$data['gift_price']);
        $this->setData('currency', trim($data['currency']));
        $this->setData('discount_details', $this->getHelper('Data')->jsonEncode($data['discount_details']));
        $this->setData('qty_purchased', (int)$data['qty_purchased']);
        $this->setData('qty_shipped', (int)$data['qty_shipped']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($data['tax_details']));
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
        $existItem = $this->amazonFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('amazon_order_item_id', $this->getData('amazon_order_item_id'))
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