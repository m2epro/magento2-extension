<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

use Ess\M2ePro\Helper\Component\Walmart\Configuration;

class Validator extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator
{
    /** @var \Ess\M2ePro\Helper\Component\Walmart\Configuration */
    private $walmartConfigurationHelper;

    public function __construct(
        Configuration $walmartConfigurationHelper,
        \Ess\M2ePro\Helper\Module\Log $helperModuleLog,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartConfigurationHelper = $walmartConfigurationHelper;
        parent::__construct($helperModuleLog, $helperData, $helperFactory, $modelFactory, $data);
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (!$this->validateMagentoProductType()) {
            return false;
        }

        $sku = $this->getSku();
        if (empty($sku)) {
            $this->addMessage('SKU is not provided. Please, check Listing Settings.');

            return false;
        }

        if (mb_strlen($sku) > \Ess\M2ePro\Helper\Component\Walmart::SKU_MAX_LENGTH) {
            $this->addMessage('The length of SKU must be less than 50 characters.');

            return false;
        }

        if (!$this->validateWalmartProductType()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProduct()) {
            return false;
        }

        if (!$this->getListingProduct()->isNotListed() || !$this->getListingProduct()->isListable()) {
            $this->addMessage('Item is already on Walmart, or not available.');

            return false;
        }

        if ($this->getVariationManager()->isLogicalUnit()) {
            return true;
        }

        if (!$this->validateProductId()) {
            return false;
        }

        if (!$this->validateStartEndDates()) {
            return false;
        }

        if (!$this->validatePrice()) {
            return false;
        }

        if ($this->getVariationManager()->isPhysicalUnit() && !$this->validatePhysicalUnitMatching()) {
            return false;
        }

        return true;
    }

    private function validateWalmartProductType(): bool
    {
        if (
            $this->getWalmartMarketplace()->isSupportedProductType()
            && !$this->getWalmartListingProduct()->isExistsProductType()
        ) {
            $this->addMessage('Product Type are not set.');

            return false;
        }

        return true;
    }

    //########################################

    private function getSku()
    {
        if (isset($this->data['sku'])) {
            return $this->data['sku'];
        }

        $params = $this->getParams();
        if (!isset($params['sku'])) {
            return null;
        }

        return $params['sku'];
    }

    //########################################

    protected function getIdentifierFromConfiguration()
    {
        if ($this->walmartConfigurationHelper->isProductIdOverrideModeAll()) {
            return Configuration::PRODUCT_ID_OVERRIDE_CUSTOM_CODE;
        }

        if ($this->walmartConfigurationHelper->isProductIdModeNotSet()) {
            return null;
        }

        return $this->getWalmartListingProduct()->getActualMagentoProduct()->getAttributeValue(
            $this->walmartConfigurationHelper->getProductIdCustomAttribute()
        );
    }
}
