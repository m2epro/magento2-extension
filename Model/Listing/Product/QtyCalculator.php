<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

use Ess\M2ePro\Model\Exception\Logic;

abstract class QtyCalculator extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var null|array
     */
    protected $source = NULL;

    /**
     * @var null|\Ess\M2ePro\Model\Listing\Product
     */
    private $product = NULL;

    /**
     * @var null|int
     */
    private $productValueCache = NULL;

    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $product
     * @return \Ess\M2ePro\Model\Listing\Product\PriceCalculator
     */
    public function setProduct(\Ess\M2ePro\Model\Listing\Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getProduct()
    {
        if (is_null($this->product)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Initialize all parameters first.');
        }

        return $this->product;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->getProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
     */
    protected function getComponentListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    protected function getSellingFormatTemplate()
    {
        return $this->getComponentProduct()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
     */
    protected function getComponentSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @param null|string $key
     * @return array|mixed
     */
    protected function getSource($key = NULL)
    {
        if (is_null($this->source)) {
            $this->source = $this->getComponentSellingFormatTemplate()->getQtySource();
        }

        return (!is_null($key) && isset($this->source[$key])) ?
                $this->source[$key] : $this->source;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
     */
    protected function getComponentProduct()
    {
        return $this->getProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected function getMagentoProduct()
    {
        return $this->getProduct()->getMagentoProduct();
    }

    //########################################

    public function getProductValue()
    {
        if (!is_null($this->productValueCache)) {
            return $this->productValueCache;
        }

        $value = $this->getClearProductValue();

        $value = $this->applySellingFormatTemplateModifications($value);
        $value < 0 && $value = 0;

        return $this->productValueCache = (int)floor($value);
    }

    public function getVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        $value = $this->getClearVariationValue($variation);

        $value = $this->applySellingFormatTemplateModifications($value);
        $value < 0 && $value = 0;

        return (int)floor($value);
    }

    //########################################

    protected function getClearProductValue()
    {
        switch ($this->getSource('mode')) {

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE:
                $value = 1;
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER:
                $value = (int)$this->getSource('value');
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE:
                $value = (int)$this->getMagentoProduct()->getAttributeValue($this->getSource('attribute'));
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED:
                $value = (int)$this->getMagentoProduct()->getQty(false);
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT:
                $value = (int)$this->getMagentoProduct()->getQty(true);
                break;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown Mode in Database.');
        }

        return $value;
    }

    protected function getClearVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        if ($this->getMagentoProduct()->isConfigurableType() ||
            $this->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
            $this->getMagentoProduct()->isGroupedType() ||
            $this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()
        ) {

            $options = $variation->getOptions(true);
            $value = $this->getOptionBaseValue(reset($options));

        } else if ($this->getMagentoProduct()->isBundleType()) {

            $optionsQtyList = array();
            $optionsQtyArray = array();

            // grouping qty by product id
            foreach ($variation->getOptions(true) as $option) {
                if (!$option->getProductId()) {
                    continue;
                }

                $optionsQtyArray[$option->getProductId()][] = $this->getOptionBaseValue($option);
            }

            foreach ($optionsQtyArray as $optionQty) {
                $optionsQtyList[] = floor($optionQty[0]/count($optionQty));
            }

            $value = min($optionsQtyList);

        } else {
            throw new Logic('Unknown Product type.',
                array(
                    'listing_product_id' => $this->getProduct()->getId(),
                    'product_id' => $this->getMagentoProduct()->getProductId(),
                    'type'       => $this->getMagentoProduct()->getTypeId()
                ));
        }

        return $value;
    }

    //########################################

    protected function getOptionBaseValue(\Ess\M2ePro\Model\Listing\Product\Variation\Option $option)
    {
        switch ($this->getSource('mode')) {
            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_SINGLE:
                $value = 1;
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER:
                $value = (int)$this->getSource('value');
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE:
                $value = (int)$option->getMagentoProduct()->getAttributeValue($this->getSource('attribute'));
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED:
                $value = (int)$option->getMagentoProduct()->getQty(false);
                break;

            case \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT:
                $value = (int)$option->getMagentoProduct()->getQty(true);
                break;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Unknown Mode in Database.');
        }

        return $value;
    }

    //########################################

    protected function applySellingFormatTemplateModifications($value)
    {
        $mode = $this->getSource('mode');

        if ($mode != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE &&
            $mode != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED &&
            $mode != \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {
            return $value;
        }

        $value = $this->applyValuePercentageModifications($value);
        $value = $this->applyValueMinMaxModifications($value);

        return $value;
    }

    // ---------------------------------------

    protected function applyValuePercentageModifications($value)
    {
        $percents = $this->getSource('qty_percentage');

        if ($value <= 0 || $percents < 0 || $percents == 100) {
            return $value;
        }

        $roundingFunction = (bool)(int)$this->moduleConfig->getGroupValue('/qty/percentage/','rounding_greater')
            ? 'ceil' : 'floor';

        return (int)$roundingFunction(($value/100) * $percents);
    }

    protected function applyValueMinMaxModifications($value)
    {
        if ($value <= 0 || !$this->getSource('qty_modification_mode')) {
            return $value;
        }

        $minValue = $this->getSource('qty_min_posted_value');
        $value < $minValue && $value = 0;

        $maxValue = $this->getSource('qty_max_posted_value');
        $value > $maxValue && $value = $maxValue;

        return $value;
    }

    //########################################
}