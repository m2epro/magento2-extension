<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Price extends AbstractModel
{
    private const PRICE_DISCOUNT_MAP_EXPOSURE_NONE = 'None';
    private const PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT = 'DuringCheckout';
    private const PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT = 'PreCheckout';

    protected \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getBuilderData(): array
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

    // ----------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getPriceDiscountStpData(): array
    {
        if (
            !$this->getEbayListingProduct()->isListingTypeFixed()
            || !$this->getEbayListingProduct()->isPriceDiscountStp()
        ) {
            return [];
        }

        $data = [
            'original_retail_price' => $this->getEbayListingProduct()->getPriceDiscountStp(),
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
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getBestOfferData(): array
    {
        $data = [
            'bestoffer_mode' => $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isBestOfferEnabled(),
        ];

        if ($data['bestoffer_mode']) {
            $data['bestoffer_accept_price'] = $this->getEbayListingProduct()->getBestOfferAcceptPrice();
            $data['bestoffer_reject_price'] = $this->getEbayListingProduct()->getBestOfferRejectPrice();
        }

        $data['best_offer_hash'] = $this->dataHelper->hashString(
            \Ess\M2ePro\Helper\Json::encode($data),
            'md5'
        );

        return $data;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getPriceDiscountMapData(): array
    {
        if (
            !$this->getEbayListingProduct()->isListingTypeFixed()
            || !$this->getEbayListingProduct()->isPriceDiscountMap()
        ) {
            return [];
        }

        $data = [
            'minimum_advertised_price' => $this->getEbayListingProduct()->getPriceDiscountMap(),
        ];

        $exposure = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
        $data['minimum_advertised_price_exposure'] = self::getPriceDiscountMapExposureType($exposure);

        return ['price_discount_map' => $data];
    }

    public static function getPriceDiscountMapExposureType(int $type): string
    {
        switch ($type) {
            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_DURING_CHECKOUT;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_PRE_CHECKOUT;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_NONE:
            default:
                return self::PRICE_DISCOUNT_MAP_EXPOSURE_NONE;
        }
    }

    //########################################
}
