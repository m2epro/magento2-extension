<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class General extends AbstractModel
{
    public const LISTING_TYPE_AUCTION  = 'Chinese';
    public const LISTING_TYPE_FIXED    = 'FixedPriceItem';

    public const PRODUCT_DETAILS_DOES_NOT_APPLY = 'Does Not Apply';
    public const PRODUCT_DETAILS_UNBRANDED = 'Unbranded';

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $config;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $config,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->config = $config;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->translation = $translation;
    }

    public function getBuilderData()
    {
        $data = [
            'duration'   => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getDuration(),
            'is_private' => $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isPrivateListing(),
            'currency'   => $this->getEbayMarketplace()->getCurrency(),
            'hit_counter'          => $this->getEbayListingProduct()->getEbayDescriptionTemplate()->getHitCounterType(),
            'listing_enhancements' => $this->getEbayListingProduct()->getEbayDescriptionTemplate()->getEnhancements(),
            'product_details'      => $this->getProductDetailsData()
        ];

        $data['listing_type'] = $this->getEbayListingProduct()->isListingTypeFixed()
            ? self::LISTING_TYPE_FIXED
            : self::LISTING_TYPE_AUCTION;

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isRestrictedToBusinessEnabled()) {
            $data['restricted_to_business'] = $this->getEbayListingProduct()
                ->getEbaySellingFormatTemplate()
                ->isRestrictedToBusinessEnabled();
        }

        return $data;
    }

    private function getProductDetailsData(): array
    {
        if ($this->isVariationItem) {
            return [];
        }

        $data = array_merge(
            $this->getProductsIdentifiersData(),
            $this->getMPNAndBrandData()
        );

        if (empty($data)) {
            return $data;
        }

        $template = $this->getEbayListingProduct()->getEbayDescriptionTemplate();
        $data['include_ebay_details'] = $template->isProductDetailsIncludeEbayDetails();
        $data['include_image'] = $template->isProductDetailsIncludeImage();

        return $data;
    }

    private function getProductsIdentifiersData(): array
    {
        $data = [];

        foreach (['isbn', 'epid', 'upc', 'ean'] as $identifier) {
            if ($this->config->isProductIdModeNone($identifier)) {
                continue;
            }

            if ($this->config->isProductIdModeDoesNotApply($identifier)) {
                $data[$identifier] = self::PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            $attribute = $this->config->getProductIdAttribute($identifier);

            if ($attribute === null) {
                continue;
            }

            $this->searchNotFoundAttributes();
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($attribute);

            if (!$this->processNotFoundAttributes(strtoupper($identifier)) || !$attributeValue) {
                continue;
            }

            $data[$identifier] = $attributeValue;
        }

        if (empty($data)) {
            return $data;
        }

        return $this->deleteNotAllowedIdentifier($data);
    }

    private function deleteNotAllowedIdentifier(array $data)
    {
        $categoryId = $this->getEbayListingProduct()->getCategoryTemplateSource()->getCategoryId();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = $this->componentEbayCategoryEbay->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean', 'upc', 'isbn', 'epid'] as $identifier) {
            $key = $identifier.'_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);

                $this->addWarningMessage(
                    $this->translation->__(
                        'The value of %type% was not sent because it is not allowed in this Category',
                        $this->translation->__(strtoupper($identifier))
                    )
                );
            }
        }

        return $data;
    }

    private function getMPNAndBrandData(): array
    {
        $descriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();
        $data = [];

        foreach (['brand', 'mpn'] as $type) {

            if ($descriptionTemplate->isProductDetailsModeNone($type)) {
                continue;
            }

            if ($descriptionTemplate->isProductDetailsModeDoesNotApply($type)) {
                $data[$type] = ($type == 'brand')
                    ? self::PRODUCT_DETAILS_UNBRANDED
                    : self::PRODUCT_DETAILS_DOES_NOT_APPLY;

                continue;
            }

            $this->searchNotFoundAttributes();
            $tempValue = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getProductDetail($type);

            if (!$this->processNotFoundAttributes(strtoupper($type)) || !$tempValue) {
                continue;
            }

            $data[$type] = $tempValue;
        }

        if (empty($data)) {
            return $data;
        }

        return $this->deleteMPNifBrandIsNotSelected($data);
    }

    private function deleteMPNifBrandIsNotSelected(array $data): array
    {
        if (empty($data['brand'])) {
            unset($data['mpn']);
            return $data;
        }

        if ($data['brand'] == self::PRODUCT_DETAILS_UNBRANDED) {
            $data['mpn'] = self::PRODUCT_DETAILS_DOES_NOT_APPLY;
            return $data;
        }

        if (empty($data['mpn'])) {
            $data['mpn'] = self::PRODUCT_DETAILS_DOES_NOT_APPLY;
            return $data;
        }

        return $data;
    }
}
