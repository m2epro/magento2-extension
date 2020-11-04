<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Item;

use Ess\M2ePro\Helper\Data as Helper;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Item\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\Item\ProxyObject
{
    //########################################

    /**
     * @return float
     */
    public function getOriginalPrice()
    {
        $price = $this->item->getPrice()
            + $this->item->getGiftPrice()
            - $this->item->getDiscountAmount();

        if ($this->getProxyOrder()->isTaxModeNone() && $this->hasTax()) {
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
     * @return array|null
     */
    public function getGiftMessage()
    {
        $giftMessage = $this->item->getGiftMessage();
        if (empty($giftMessage)) {
            return parent::getGiftMessage();
        }

        return [
            'sender'    => '',
            'recipient' => '',
            'message'   => $this->item->getGiftMessage()
        ];
    }

    //########################################

    /**
     * @return array
     * @throws \Exception
     */
    public function getAdditionalData()
    {
        if (count($this->additionalData) == 0) {
            $this->additionalData[Helper::CUSTOM_IDENTIFIER]['pretended_to_be_simple'] = $this->pretendedToBeSimple();
            $this->additionalData[Helper::CUSTOM_IDENTIFIER]['items'][] = [
                'order_item_id' => $this->item->getWalmartOrderItemId()
            ];
        }
        return $this->additionalData;
    }

    //########################################
}
