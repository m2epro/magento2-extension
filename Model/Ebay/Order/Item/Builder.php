<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Item;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\Item\Builder
 */
class Builder extends AbstractModel
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(array $data)
    {
        // ---------------------------------------
        $this->setData('order_id', $data['order_id']);
        $this->setData('transaction_id', $data['transaction_id']);
        $this->setData('selling_manager_id', $data['selling_manager_id']);
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('item_id', $data['item_id']);
        $this->setData('title', $data['title']);
        $this->setData('sku', $data['sku']);
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('price', (float)$data['selling']['price']);
        $this->setData('qty_purchased', (int)$data['selling']['qty_purchased']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($data['selling']['tax_details']));
        $this->setData('final_fee', (float)$data['selling']['final_fee']);
        $this->setData('waste_recycling_fee', (float)$data['selling']['waste_recycling_fee']);
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('variation_details', $this->getHelper('Data')->jsonEncode($data['variation_details']));
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('tracking_details', $this->getHelper('Data')->jsonEncode($data['tracking_details']));
        // ---------------------------------------
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $item */
        $item = $this->ebayFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('item_id', $this->getData('item_id'))
            ->addFieldToFilter('transaction_id', $this->getData('transaction_id'))
            ->getFirstItem();

        foreach ($this->getData() as $key => $value) {
            if (!$item->getId() || ($item->hasData($key) && $item->getData($key) != $value)) {
                $item->addData($this->getData());
                $item->save();
                break;
            }
        }

        $ebayItem = $item->getChildObject();
        foreach ($this->getData() as $key => $value) {
            if (!$item->getId() || ($ebayItem->hasData($key) && $ebayItem->getData($key) != $value)) {
                $ebayItem->addData($this->getData());
                $ebayItem->save();
                break;
            }
        }

        return $item;
    }

    //########################################
}
