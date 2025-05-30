<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

class RequestData extends \Ess\M2ePro\Model\Listing\Product\Action\RequestData
{
    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasQty()
    {
        return !$this->isVariationItem() && isset($this->getData()['qty']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasPrice()
    {
        return !$this->isVariationItem() &&
            (
                $this->hasPriceFixed() ||
                $this->hasPriceStart() ||
                $this->hasPriceReserve() ||
                $this->hasPriceBuyItNow()
            );
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasPriceFixed()
    {
        return !$this->isVariationItem() && isset($this->getData()['price_fixed']);
    }

    /**
     * @return bool
     */
    public function hasPriceStart()
    {
        return !$this->isVariationItem() && isset($this->getData()['price_start']);
    }

    /**
     * @return bool
     */
    public function hasPriceReserve()
    {
        return !$this->isVariationItem() && isset($this->getData()['price_reserve']);
    }

    /**
     * @return bool
     */
    public function hasPriceBuyItNow()
    {
        return !$this->isVariationItem() && isset($this->getData()['price_buyitnow']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasSku()
    {
        return isset($this->getData()['sku']);
    }

    /**
     * @return bool
     */
    public function hasPrimaryCategory()
    {
        return isset($this->getData()['category_main_id']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasTitle()
    {
        return isset($this->getData()['title']);
    }

    /**
     * @return bool
     */
    public function hasSubtitle()
    {
        return isset($this->getData()['subtitle']);
    }

    /**
     * @return bool
     */
    public function hasDescription()
    {
        return isset($this->getData()['description']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasDuration()
    {
        return isset($this->getData()['duration']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasImages()
    {
        return isset($this->getData()['images']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isVariationItem()
    {
        return isset($this->getData()['is_variation_item']) && $this->getData()['is_variation_item'];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasVariations()
    {
        return $this->isVariationItem() && isset($this->getData()['variation']);
    }

    /**
     * @return bool
     */
    public function hasVariationsImages()
    {
        return $this->isVariationItem() && isset($this->getData()['variation_image']);
    }

    // ---------------------------------------

    public function getQty()
    {
        return $this->hasQty() ? $this->getData()['qty'] : null;
    }

    // ---------------------------------------

    public function getPriceFixed()
    {
        return $this->hasPriceFixed() ? $this->getData()['price_fixed'] : null;
    }

    public function getPriceStart()
    {
        return $this->hasPriceStart() ? $this->getData()['price_start'] : null;
    }

    public function getPriceReserve()
    {
        return $this->hasPriceReserve() ? $this->getData()['price_reserve'] : null;
    }

    public function getPriceBuyItNow()
    {
        return $this->hasPriceBuyItNow() ? $this->getData()['price_buyitnow'] : null;
    }

    // ---------------------------------------

    public function getSku()
    {
        return $this->hasSku() ? $this->getData()['sku'] : null;
    }

    public function getPrimaryCategory()
    {
        return $this->hasPrimaryCategory() ? $this->getData()['category_main_id'] : null;
    }

    // ---------------------------------------

    public function getTitle()
    {
        return $this->hasTitle() ? $this->getData()['title'] : null;
    }

    public function getSubtitle()
    {
        return $this->hasSubtitle() ? $this->getData()['subtitle'] : null;
    }

    public function getDescription()
    {
        return $this->hasDescription() ? $this->getData()['description'] : null;
    }

    public function getProductDetailsIncludeEbayDetails(): ?string
    {
        return $this->getProductDetails()['include_ebay_details'] ?? null;
    }

    public function getProductDetailsIncludeImage(): ?string
    {
        return $this->getProductDetails()['include_image'] ?? null;
    }

    // ---------------------------------------

    public function getDuration()
    {
        return $this->hasDuration() ? $this->getData()['duration'] : null;
    }

    // ---------------------------------------

    public function getImages()
    {
        return $this->hasImages() ? $this->getData()['images'] : null;
    }

    // ---------------------------------------

    /**
     * @see \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::getBuilderData()
     */
    public function getProductDetails(): array
    {
        return $this->getData()['product_details'] ?? [];
    }

    public function getProductDetailsUpc(): ?string
    {
        return $this->getProductDetails()['upc'] ?? null;
    }

    public function getProductDetailsEpid(): ?string
    {
        return $this->getProductDetails()['epid'] ?? null;
    }

    public function getProductDetailsEan(): ?string
    {
        return $this->getProductDetails()['ean'] ?? null;
    }

    public function getProductDetailsIsbn(): ?string
    {
        return $this->getProductDetails()['isbn'] ?? null;
    }

    public function getProductDetailsBrand(): ?string
    {
        return $this->getProductDetails()['brand'] ?? null;
    }

    public function getProductDetailsMpn(): ?string
    {
        return $this->getProductDetails()['mpn'] ?? null;
    }

    // ---------------------------------------

    public function getVariations()
    {
        return $this->hasVariations() ? $this->getData()['variation'] : null;
    }

    public function getVariationsImages()
    {
        return $this->hasVariationsImages() ? $this->getData()['variation_image'] : null;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getImagesCount()
    {
        if (!$this->hasImages()) {
            return 0;
        }

        $images = $this->getImages();
        $images = isset($images['images']) ? $images['images'] : [];

        return count($images);
    }

    /**
     * @return int
     */
    public function getTotalImagesCount()
    {
        return $this->getImagesCount() + $this->getVariationsImagesCount();
    }

    // ---------------------------------------

    /**
     * @return int|null
     */
    public function getVariationQty()
    {
        if (!$this->hasVariations()) {
            return null;
        }

        $qty = 0;
        foreach ($this->getVariations() as $variationData) {
            $qty += (int)$variationData['qty'];
        }

        return $qty;
    }

    /**
     * @param bool $calculateWithEmptyQty
     *
     * @return float|null
     */
    public function getVariationPrice($calculateWithEmptyQty = true)
    {
        return $this->getVariationMinPrice($calculateWithEmptyQty);
    }

    /**
     * @param bool $calculateWithEmptyQty
     *
     * @return float|null
     */
    public function getVariationMinPrice($calculateWithEmptyQty = true)
    {
        if (!$this->hasVariations()) {
            return null;
        }

        $price = null;

        foreach ($this->getVariations() as $variationData) {
            if ($variationData['delete'] || !isset($variationData['price'])) {
                continue;
            }

            if (!$calculateWithEmptyQty && (int)$variationData['qty'] <= 0) {
                continue;
            }

            if ($price !== null && (float)$variationData['price'] >= $price) {
                continue;
            }

            $price = (float)$variationData['price'];
        }

        return (float)$price;
    }

    /**
     * @param bool $calculateWithEmptyQty
     *
     * @return float|null
     */
    public function getVariationMaxPrice($calculateWithEmptyQty = true)
    {
        if (!$this->hasVariations()) {
            return null;
        }

        $price = null;

        foreach ($this->getVariations() as $variationData) {
            if ($variationData['delete'] || !isset($variationData['price'])) {
                continue;
            }

            if (!$calculateWithEmptyQty && (int)$variationData['qty'] <= 0) {
                continue;
            }

            if ($price !== null && (float)$variationData['price'] <= $price) {
                continue;
            }

            $price = (float)$variationData['price'];
        }

        return (float)$price;
    }

    /**
     * @return int
     */
    public function getVariationsImagesCount()
    {
        if (!$this->hasVariationsImages()) {
            return 0;
        }

        $images = $this->getVariationsImages();
        $images = isset($images['images']) ? $images['images'] : [];

        return count($images);
    }

    // ---------------------------------------
}
