<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Item;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\Item\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\Item\ProxyObject
{
    //########################################

    /**
     * @return float
     */
    public function getOriginalPrice()
    {
        $price = $this->item->getPrice();

        if (($this->getProxyOrder()->isTaxModeNone() && $this->hasTax()) || $this->isVatTax()) {
            $price += $this->item->getTaxAmount();
        }

        return $price;
    }

    /**
     * @return int
     */
    public function getOriginalQty()
    {
        return $this->item->getQtyPurchased();
    }

    //########################################

    /**
     * @return float
     */
    public function getTaxRate()
    {
        return $this->item->getTaxRate();
    }

    //########################################

    public function getWasteRecyclingFee()
    {
        return $this->item->getWasteRecyclingFee();
    }

    //########################################

    /**
     * @return array
     */
    public function getAdditionalData()
    {
        if (count($this->additionalData) == 0) {
            $this->additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'][] = [
                'item_id' => $this->item->getItemId(),
                'transaction_id' => $this->item->getTransactionId()
            ];
        }
        return $this->additionalData;
    }

    //########################################
}
