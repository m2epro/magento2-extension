<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Item;

class Proxy extends \Ess\M2ePro\Model\Order\Item\Proxy
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

        return array(
            'sender'    => '',
            'recipient' => '',
            'message'   => $this->item->getGiftMessage()
        );
    }

    //########################################

    /**
     * @return array
     */
    public function getAdditionalData()
    {
        if (count($this->additionalData) == 0) {
            $this->additionalData[\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER]['items'][] = array(
                'order_item_id' => $this->item->getAmazonOrderItemId()
            );
        }
        return $this->additionalData;
    }

    //########################################
}