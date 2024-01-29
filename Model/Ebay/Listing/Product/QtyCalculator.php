<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing getComponentListing()
 * @method \Ess\M2ePro\Model\Ebay\Template\SellingFormat getComponentSellingFormatTemplate()
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product getComponentProduct()
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\QtyCalculator
 */
class QtyCalculator extends \Ess\M2ePro\Model\Listing\Product\QtyCalculator
{
    /**
     * @var bool
     */
    private $isMagentoMode = false;

    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $ruleFactory;
    /** @var \Ess\M2ePro\Model\Magento\ProductFactory */
    private $productFactory;

    /**
     * @param \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Model\Magento\ProductFactory $productFactory,
        array $data = []
    ) {
        parent::__construct($moduleConfiguration, $helperFactory, $modelFactory, $data);
        $this->ruleFactory = $ruleFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setIsMagentoMode($value)
    {
        $this->isMagentoMode = (bool)$value;

        return $this;
    }

    /**
     * @return bool
     */
    protected function getIsMagentoMode()
    {
        return $this->isMagentoMode;
    }

    //########################################

    public function getProductValue()
    {
        if ($this->getIsMagentoMode()) {
            return (int)$this->getMagentoProduct()->getQty(true);
        }

        return parent::getProductValue();
    }

    //########################################

    public function getVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        if ($variation->getChildObject()->isDelete()) {
            return 0;
        }

        $qty = parent::getVariationValue($variation);
        $ebaySynchronizationTemplate = $variation->getListingProduct()
            ->getChildObject()
            ->getEbaySynchronizationTemplate();

        if ($ebaySynchronizationTemplate->isStopWhenQtyCalculatedHasValue()) {
            $minQty = (int)$ebaySynchronizationTemplate->getStopWhenQtyCalculatedHasValueMin();

            if ($qty <= $minQty || $this->isVariationHasStopAdvancedRules($variation)) {
                return 0;
            }
        }

        return $qty;
    }

    //########################################

    protected function getOptionBaseValue(\Ess\M2ePro\Model\Listing\Product\Variation\Option $option)
    {
        if (
            !$option->getMagentoProduct()->isStatusEnabled() ||
            !$option->getMagentoProduct()->isStockAvailability()
        ) {
            return 0;
        }

        if (
            $this->getIsMagentoMode() ||
            $this->getSource('mode') == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT
        ) {
            if (
                !$this->getMagentoProduct()->isStatusEnabled() ||
                !$this->getMagentoProduct()->isStockAvailability()
            ) {
                return 0;
            }
        }

        if ($this->getIsMagentoMode()) {
            return (int)$option->getMagentoProduct()->getQty(true);
        }

        return parent::getOptionBaseValue($option);
    }

    //########################################

    protected function applySellingFormatTemplateModifications($value)
    {
        if ($this->getIsMagentoMode()) {
            return $value;
        }

        return parent::applySellingFormatTemplateModifications($value);
    }

    private function isVariationHasStopAdvancedRules(\Ess\M2ePro\Model\Listing\Product\Variation $variation): bool
    {
        $ebaySynchronizationTemplate = $variation->getListingProduct()
                                                 ->getChildObject()
                                                 ->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isStopAdvancedRulesEnabled()) {
            return false;
        }

        $ruleModel = $this->ruleFactory->create(
            \Ess\M2ePro\Model\Ebay\Template\Synchronization::STOP_ADVANCED_RULES_PREFIX,
            $variation->getListingProduct()->getListing()->getStoreId()
        );

        $ruleModel->loadFromSerialized($ebaySynchronizationTemplate->getStopAdvancedRulesFilters());

        if (empty($ruleModel->getConditions()->getConditions())) {
            return false;
        }

        $productIdVariation = $variation->getChildObject()->getVariationProductId();
        $productVariation = $this->productFactory->create();
        $productVariation->setProductId($productIdVariation);

        if ($ruleModel->validate($productVariation->getProduct())) {
            return true;
        }

        return false;
    }
}
