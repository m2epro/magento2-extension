<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Price
 */
class Price extends AbstractModel
{
    const PRICE_DISCOUNT_MAP_EXPOSURE_NONE             = 'None';
    const PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT  = 'DuringCheckout';
    const PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT     = 'PreCheckout';

    //########################################

    public function getBuilderData()
    {
        $data = [];

        if ($this->getEbayListingProduct()->isListingTypeFixed()) {
            $data['price_fixed'] = $this->getEbayListingProduct()->getFixedPrice();
        } else {
            $data['price_start'] = $this->getEbayListingProduct()->getStartPrice();
            $data['price_reserve'] = $this->getEbayListingProduct()->getReservePrice();
            $data['price_buyitnow'] = $this->getEbayListingProduct()->getBuyItNowPrice();
        }

        $data = array_merge(
            $data,
            $this->getPriceDiscountStpData(),
            $this->getPriceDiscountMapData(),
            $this->getBestOfferData()
        );

        return $data;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPriceDiscountStpData()
    {
        if (!$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getEbayListingProduct()->isPriceDiscountStp()) {
            return [];
        }

        $data = [
            'original_retail_price' => $this->getEbayListingProduct()->getPriceDiscountStp()
        ];

        if ($this->getEbayMarketplace()->isStpAdvancedEnabled()) {
            $data = array_merge(
                $data,
                $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getPriceDiscountStpAdditionalFlags()
            );
        }

        return ['price_discount_stp' => $data];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getBestOfferData()
    {
        $data = [
            'bestoffer_mode' => $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isBestOfferEnabled(),
        ];

        if ($data['bestoffer_mode']) {
            $data['bestoffer_accept_price'] = $this->getEbayListingProduct()->getBestOfferAcceptPrice();
            $data['bestoffer_reject_price'] = $this->getEbayListingProduct()->getBestOfferRejectPrice();
        }

        return $data;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPriceDiscountMapData()
    {
        if (!$this->getEbayListingProduct()->isListingTypeFixed() ||
            !$this->getEbayListingProduct()->isPriceDiscountMap()) {
            return [];
        }

        $data = [
            'minimum_advertised_price' => $this->getEbayListingProduct()->getPriceDiscountMap(),
        ];

        $exposure = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
        $data['minimum_advertised_price_exposure'] = self::getPriceDiscountMapExposureType($exposure);

        return ['price_discount_map' => $data];
    }

    public static function getPriceDiscountMapExposureType($type)
    {
        switch ($type) {
            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_NONE:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_NONE;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT;

            default:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_NONE;
        }
    }

    //########################################
}
