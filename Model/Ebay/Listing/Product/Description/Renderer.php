<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Description;

use Ess\M2ePro\Model\Ebay\Template\Description as TemplateDescription;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Description\Renderer
 */
class Renderer extends \Ess\M2ePro\Model\AbstractModel
{
    public const MODE_FULL = 1;
    public const MODE_PREVIEW = 2;

    protected $renderMode = self::MODE_FULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product */
    protected $listingProduct = null;

    protected $resourceConnection;

    /** @var \Magento\Framework\Pricing\Helper\Data $priceHelper */
    protected $priceHelper;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->priceHelper = $priceHelper;
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
     *
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

    /**
     * @param string $text
     *
     * @return string
     */
    protected function insertValues($text)
    {
        preg_match_all("/#value\[(.+?)\]#/", $text, $matches);

        if (empty($matches[0])) {
            return $text;
        }

        $replaces = [];
        foreach ($matches[1] as $i => $attributeCode) {
            $method = 'get' . implode(array_map('ucfirst', explode('_', $attributeCode)));

            $arg = null;
            if (preg_match('/(?<=\[)(\d+?)(?=\])/', $method, $tempMatch)) {
                $arg = $tempMatch[0];
                $method = str_replace('[' . $arg . ']', '', $method);
            }

            $value = '';
            if (method_exists($this, $method)) {
                $value = $this->$method($arg);
            }

            if (in_array($attributeCode, ['fixed_price', 'start_price', 'reserve_price', 'buyitnow_price'])) {
                $value = round((float)$value, 2);
                $value = $this->priceHelper->currency($value, true, false);
            }

            if ($value !== '') {
                $replaces[$matches[0][$i]] = $value;
            }
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
            $pricesList = [];

            foreach ($this->listingProduct->getVariations(true) as $variation) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
                $pricesList[] = $variation->getChildObject()->getPrice();
            }

            $price = !empty($pricesList) ? min($pricesList) : 0;
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

        $types = [
            \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED => $helper->__('Fixed Price'),
            \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION => $helper->__('Auction'),
        ];

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
        $handlingTime = $this->listingProduct->getShippingTemplateSource()->getDispatchTime();

        $result = $this->getHelper('Module\Translation')->__('Business Day');

        if ($handlingTime > 1) {
            $result = $this->getHelper('Module\Translation')->__('Business Days');
        }

        if ($handlingTime) {
            $result = $handlingTime . ' ' . $result;
        } else {
            $result = $this->getHelper('Module\Translation')->__('Same') . ' ' . $result;
        }

        return $result;
    }

    // ---------------------------------------

    protected function getCondition()
    {
        $conditions = [
            TemplateDescription::CONDITION_EBAY_NEW => __('New'),
            TemplateDescription::CONDITION_EBAY_NEW_OTHER => __('New Other'),
            TemplateDescription::CONDITION_EBAY_NEW_WITH_DEFECT => __('New With Defects'),
            TemplateDescription::CONDITION_EBAY_CERTIFIED_REFURBISHED => __('Manufacturer Refurbished'),
            TemplateDescription::CONDITION_EBAY_EXCELLENT_REFURBISHED => __('Excellent (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_VERY_GOOD_REFURBISHED => __('Very Good (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_GOOD_REFURBISHED => __('Good (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_SELLER_REFURBISHED => __('Seller Refurbished'),
            TemplateDescription::CONDITION_EBAY_LIKE_NEW => __('Like New'),
            TemplateDescription::CONDITION_EBAY_PRE_OWNED_EXCELLENT => __('Excellent (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_USED_EXCELLENT => __('Good (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_PRE_OWNED_FAIR => __('Fair (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_VERY_GOOD => __('Very Good'),
            TemplateDescription::CONDITION_EBAY_GOOD => __('Good'),
            TemplateDescription::CONDITION_EBAY_ACCEPTABLE => __('Acceptable'),
            TemplateDescription::CONDITION_EBAY_NOT_WORKING => __('For Parts or Not Working'),
        ];

        $condition = $this->listingProduct->getDescriptionTemplateSource()->getCondition();

        if (isset($conditions[$condition])) {
            return $conditions[$condition];
        }

        return __('N/A');
    }

    protected function getConditionDescription()
    {
        return $this->listingProduct->getDescriptionTemplateSource()->getConditionNote();
    }

    //########################################

    protected function getPrimaryCategoryId()
    {
        $source = $this->listingProduct->getCategoryTemplateSource();

        return $source ? $source->getCategoryId() : 'N/A';
    }

    protected function getSecondaryCategoryId()
    {
        $source = $this->listingProduct->getCategorySecondaryTemplateSource();

        return $source ? $source->getCategoryId() : 'N/A';
    }

    protected function getStorePrimaryCategoryId()
    {
        $source = $this->listingProduct->getStoreCategoryTemplateSource();

        return $source ? $source->getCategoryId() : 'N/A';
    }

    protected function getStoreSecondaryCategoryId()
    {
        $source = $this->listingProduct->getStoreCategorySecondaryTemplateSource();

        return $source ? $source->getCategoryId() : 'N/A';
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

        $tableDictShipping = $this->getHelper('Module_Database_Structure')
                                  ->getTableNameWithPrefix('m2epro_ebay_dictionary_shipping');

        // table m2epro_ebay_dictionary_marketplace
        $dbSelect = $connection
            ->select()
            ->from($tableDictShipping, 'title')
            ->where('`ebay_id` = ?', $service->getShippingValue())
            ->where('`marketplace_id` = ?', (int)$this->listingProduct->getMarketplace()->getId());

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
        $tableDictShipping = $this->getHelper('Module_Database_Structure')
                                  ->getTableNameWithPrefix('m2epro_ebay_dictionary_shipping');
        // ---------------------------------------

        // table m2epro_ebay_dictionary_marketplace
        // ---------------------------------------
        $dbSelect = $connection
            ->select()
            ->from($tableDictShipping, 'title')
            ->where('`ebay_id` = ?', $service->getShippingValue())
            ->where('`marketplace_id` = ?', (int)$this->listingProduct->getMarketplace()->getId());

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
