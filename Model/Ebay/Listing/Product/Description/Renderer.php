<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Description;

class Renderer extends \Ess\M2ePro\Model\AbstractModel
{
    const MODE_FULL = 1;
    const MODE_PREVIEW = 2;

    protected $renderMode = self::MODE_FULL;

    /* @var \Ess\M2ePro\Model\Ebay\Listing\Product */
    protected $listingProduct = NULL;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return int
     */
    public function getRenderMode()
    {
        return $this->renderMode;
    }

    /**
     * @param int $renderMode
     */
    public function setRenderMode($renderMode)
    {
        $this->renderMode = $renderMode;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Ebay\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    //########################################

    public function parseTemplate($text)
    {
        $text = $this->insertValues($text);
        return $text;
    }

    //########################################

    protected function insertValues($text)
    {
        preg_match_all("/#value\[(.+?)\]#/", $text, $matches);

        if (!count($matches[0])) {
            return $text;
        }

        $replaces = array();
        foreach ($matches[1] as $i => $attributeCode) {
            $method = 'get'.implode(array_map('ucfirst',explode('_', $attributeCode)));

            $arg = NULL;
            if (preg_match('/(?<=\[)(\d+?)(?=\])/',$method,$tempMatch)) {
                $arg = $tempMatch[0];
                $method = str_replace('['.$arg.']','',$method);
            }

            method_exists($this,$method) && $replaces[$matches[0][$i]] = $this->$method($arg);
        }

        $text = str_replace(array_keys($replaces), array_values($replaces), $text);

        return $text;
    }

    //########################################

    /**
     * @return int
     */
    protected function getQty()
    {
        return (int)$this->listingProduct->getQty();
    }

    // ---------------------------------------

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getFixedPrice()
    {
        if (!$this->listingProduct->isListingTypeFixed()) {
            return 'N/A';
        }

        if ($this->listingProduct->isVariationsReady()) {

            $pricesList = array();

            foreach ($this->listingProduct->getVariations(true) as $variation) {
                /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
                $pricesList[] = $variation->getChildObject()->getPrice();
            }

            $price = count($pricesList) > 0 ? min($pricesList) : 0;

        } else {
            $price = $this->listingProduct->getFixedPrice();
        }

        if (empty($price)) {
            return 'N/A';
        }

        return sprintf('%01.2f', $price);
    }

    protected function getStartPrice()
    {
        if (!$this->listingProduct->isListingTypeAuction()) {
            return 'N/A';
        }

        $price = $this->listingProduct->getStartPrice();

        if (empty($price)) {
            return 'N/A';
        }

        return sprintf('%01.2f', $price);
    }

    protected function getReservePrice()
    {
        if (!$this->listingProduct->isListingTypeAuction()) {
            return 'N/A';
        }

        $price = $this->listingProduct->getReservePrice();

        if (empty($price)) {
            return 'N/A';
        }

        return sprintf('%01.2f', $price);
    }

    protected function getBuyItNowPrice()
    {
        if (!$this->listingProduct->isListingTypeAuction()) {
            return 'N/A';
        }

        $price = $this->listingProduct->getBuyItNowPrice();

        if (empty($price)) {
            return 'N/A';
        }

        return sprintf('%01.2f', $price);
    }

    //########################################

    protected function getTitle()
    {
        return $this->listingProduct->getDescriptionTemplateSource()->getTitle();
    }

    protected function getSubtitle()
    {
        return $this->listingProduct->getDescriptionTemplateSource()->getSubTitle();
    }

    // ---------------------------------------

    protected function getListingType()
    {
        $helper = $this->getHelper('Module\Translation');

        $types = array(
           \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED => $helper->__('Fixed Price'),
           \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION => $helper->__('Auction'),
        );

        $type = $this->listingProduct->getSellingFormatTemplateSource()->getListingType();

        if (isset($types[$type])) {
            return $types[$type];
        }

        return 'N/A';
    }

    protected function getListingDuration()
    {
        $durations = $this->getHelper('Component\Ebay')->getAvailableDurations();

        $duration = $this->listingProduct->getSellingFormatTemplateSource()->getDuration();

        if (isset($durations[$duration])) {
            return $durations[$duration];
        }

        return 'N/A';
    }

    protected function getHandlingTime()
    {
        $handlingTime = $this->listingProduct->getShippingTemplate()->getDispatchTime();

        $result = $this->getHelper('Module\Translation')->__('Business Day');

        if ($handlingTime > 1) {
            $result = $this->getHelper('Module\Translation')->__('Business Days');
        }

        if ($handlingTime) {
            $result = $handlingTime.' '.$result;
        } else {
            $result = $this->getHelper('Module\Translation')->__('Same').' '.$result;
        }

        return $result;
    }

    // ---------------------------------------

    protected function getCondition()
    {
        $conditions = array_combine(
            array(
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW_OTHER,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW_WITH_DEFECT,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_MANUFACTURER_REFURBISHED,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_SELLER_REFURBISHED,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_USED,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_VERY_GOOD,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_GOOD,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_ACCEPTABLE,
               \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NOT_WORKING,
            ),
            array(
                $this->getHelper('Module\Translation')->__('New'),
                $this->getHelper('Module\Translation')->__('New Other'),
                $this->getHelper('Module\Translation')->__('New With Defects'),
                $this->getHelper('Module\Translation')->__('Manufacturer Refurbished'),
                $this->getHelper('Module\Translation')->__('Seller Refurbished'),
                $this->getHelper('Module\Translation')->__('Used'),
                $this->getHelper('Module\Translation')->__('Very Good'),
                $this->getHelper('Module\Translation')->__('Good'),
                $this->getHelper('Module\Translation')->__('Acceptable'),
                $this->getHelper('Module\Translation')->__('For Parts or Not Working'),
            )
        );

        $condition = $this->listingProduct->getDescriptionTemplateSource()->getCondition();

        if (isset($conditions[$condition])) {
            return $conditions[$condition];
        }

        return $this->getHelper('Module\Translation')->__('N/A');
    }

    protected function getConditionDescription()
    {
        return $this->listingProduct->getDescriptionTemplateSource()->getConditionNote();
    }

    //########################################

    protected function getPrimaryCategoryId()
    {
        if (!$this->listingProduct->isSetCategoryTemplate()) {
            return 'N/A';
        }

        $category = $this->listingProduct->getCategoryTemplateSource()->getMainCategory();
        return $category ? $category : 'N/A';
    }

    protected function getSecondaryCategoryId()
    {
        if (!$this->listingProduct->isSetOtherCategoryTemplate()) {
            return 'N/A';
        }

        $category = $this->listingProduct->getOtherCategoryTemplateSource()->getSecondaryCategory();
        return $category ? $category : 'N/A';
    }

    protected function getStorePrimaryCategoryId()
    {
        $category = $this->listingProduct->getOtherCategoryTemplateSource()->getStoreCategoryMain();
        return $category ? $category : 'N/A';
    }

    protected function getStoreSecondaryCategoryId()
    {
        $category = $this->listingProduct->getOtherCategoryTemplateSource()->getStoreCategorySecondary();
        return $category ? $category : 'N/A';
    }

    // ---------------------------------------

    protected function getPrimaryCategoryName()
    {
        $category = $this->listingProduct->getEbayMarketplace()->getCategory($this->getPrimaryCategoryId());

        if ($category) {
            return $category['title'];
        }

        return 'N/A';
    }

    protected function getSecondaryCategoryName()
    {
        $category = $this->listingProduct->getEbayMarketplace()->getCategory($this->getSecondaryCategoryId());

        if ($category) {
            return $category['title'];
        }

        return 'N/A';
    }

    protected function getStorePrimaryCategoryName()
    {
        $category = $this->listingProduct->getEbayAccount()->getEbayStoreCategory($this->getStorePrimaryCategoryId());

        if ($category) {
            return $category['title'];
        }

        return 'N/A';
    }

    protected function getStoreSecondaryCategoryName()
    {
        $category = $this->listingProduct->getEbayAccount()->getEbayStoreCategory($this->getStoreSecondaryCategoryId());

        if ($category) {
            return $category['title'];
        }

        return 'N/A';
    }

    //########################################

    protected function getDomesticShippingMethod($i)
    {
        $services = array_values($this->listingProduct->getShippingTemplate()->getLocalShippingServices());

        --$i;
        if (!isset($services[$i])) {
            return 'N/A';
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $service */
        $service = $services[$i];

        $connection = $this->resourceConnection->getConnection();

        $tableDictShipping = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_shipping');

        // table m2epro_ebay_dictionary_marketplace
        $dbSelect = $connection
            ->select()
            ->from($tableDictShipping,'title')
            ->where('`ebay_id` = ?',$service->getShippingValue())
            ->where('`marketplace_id` = ?',(int)$this->listingProduct->getMarketplace()->getId());

        $shippingMethod = $dbSelect->query()->fetchColumn();

        return $shippingMethod ? $shippingMethod : 'N/A';
    }

    protected function getDomesticShippingCost($i)
    {
        $services = ($this->listingProduct->getShippingTemplate()->getLocalShippingServices());

        --$i;
        if (!isset($services[$i])) {
            return 'N/A';
        }

        $cost = $services[$i]->getSource($this->listingProduct->getMagentoProduct())
                             ->getCost();

        if (empty($cost)) {
            return $this->getHelper('Module\Translation')->__('Free');
        }

        return sprintf('%01.2f', $cost);
    }

    protected function getDomesticShippingAdditionalCost($i)
    {
        $services = ($this->listingProduct->getShippingTemplate()->getLocalShippingServices());

        --$i;
        if (!isset($services[$i])) {
            return 'N/A';
        }

        $cost = $services[$i]->getSource($this->listingProduct->getMagentoProduct())
                             ->getCostAdditional();

        if (empty($cost)) {
            return $this->getHelper('Module\Translation')->__('Free');
        }

        return sprintf('%01.2f', $cost);
    }

    // ---------------------------------------

    protected function getInternationalShippingMethod($i)
    {
        $services = array_values($this->listingProduct->getShippingTemplate()->getInternationalShippingServices());

        --$i;
        if (!isset($services[$i])) {
            return 'N/A';
        }

        /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $service */
        $service = $services[$i];

        $connection = $this->resourceConnection->getConnection();

        // ---------------------------------------
        $tableDictShipping = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_shipping');
        // ---------------------------------------

        // table m2epro_ebay_dictionary_marketplace
        // ---------------------------------------
        $dbSelect = $connection
            ->select()
            ->from($tableDictShipping,'title')
            ->where('`ebay_id` = ?',$service->getShippingValue())
            ->where('`marketplace_id` = ?',(int)$this->listingProduct->getMarketplace()->getId());

        $shippingMethod = $dbSelect->query()->fetchColumn();

        return $shippingMethod ? $shippingMethod : 'N/A';
    }

    protected function getInternationalShippingCost($i)
    {
        $services = ($this->listingProduct->getShippingTemplate()->getInternationalShippingServices());

        --$i;
        if (!isset($services[$i]) || !$services[$i]->getShippingValue()) {
            return 'N/A';
        }

        $cost = $services[$i]->getSource($this->listingProduct->getMagentoProduct())
                             ->getCost();

        if (empty($cost)) {
            return $this->getHelper('Module\Translation')->__('Free');
        }

        return sprintf('%01.2f', $cost);
    }

    protected function getInternationalShippingAdditionalCost($i)
    {
        $services = ($this->listingProduct->getShippingTemplate()->getInternationalShippingServices());

        --$i;
        if (!isset($services[$i]) || !$services[$i]->getShippingValue()) {
            return 'N/A';
        }

        $cost = $services[$i]->getSource($this->listingProduct->getMagentoProduct())
                             ->getCostAdditional();

        if (empty($cost)) {
            return $this->getHelper('Module\Translation')->__('Free');
        }

        return sprintf('%01.2f', $cost);
    }

    //########################################

    protected function getMainImage()
    {
        if ($this->renderMode === self::MODE_FULL) {
            $mainImage = $this->listingProduct->getDescriptionTemplateSource()->getMainImage();
        } else {
            $mainImage = $this->listingProduct->getMagentoProduct()->getImage('image');
        }

        return !empty($mainImage) ? $mainImage->getUrl() : '';
    }

    protected function getGalleryImage($index)
    {
        if ($this->renderMode === self::MODE_FULL) {
            $images = array_values($this->listingProduct->getDescriptionTemplateSource()->getGalleryImages());
        } else {
            $images = array_values($this->listingProduct->getMagentoProduct()->getGalleryImages(11));

            if ($index <= 0) {
                return '';
            }
            $index--;
        }

        if (!empty($images[$index]) && $images[$index]->getUrl()) {
            return $images[$index]->getUrl();
        }

        return '';
    }

    //########################################
}