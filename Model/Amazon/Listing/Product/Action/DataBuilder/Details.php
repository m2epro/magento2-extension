<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder;

class Details extends AbstractModel
{
    private $listingProductFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);

        $this->listingProductFactory = $listingProductFactory;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getSpecifics(): array
    {
        $listingProduct = $this->getListingProduct();
        $productType = $listingProduct->getChildObject()->getProductTypeTemplate();
        if ($productType === null || !$productType->getId()) {
            return [];
        }

        $this->searchNotFoundAttributes(); // clear previously not found attributes

        $result = [
            'product_type_nick' => $productType->getNick(),
            'attributes' => $this->buildSpecificsData($productType->getSettings('settings')),
        ];
        $this->processNotFoundAttributes('Product Specifics'); // add message about not found attributes

        return $result;
    }

    /**
     * @param array $specifics
     *
     * @return array
     */
    private function buildSpecificsData(array $specifics): array
    {
        $result = [];

        foreach ($specifics as $name => $values) {
            if (empty($values)) {
                continue;
            }

            $finalValues = [];
            foreach ($values as $value) {
                if ($finalValue = $this->buildSingleSpecificData($value)) {
                    $finalValues[] = $finalValue;
                }
            }

            if (!empty($finalValues)) {
                $result[$name] = (count($finalValues) === 1) ?
                    $finalValues[0] : $finalValues;
            }
        }

        return $result;
    }

    /**
     * @param array $setting
     *
     * @return string|null
     */
    private function buildSingleSpecificData(array $setting): ?string
    {
        switch ((int)$setting['mode']) {
            case \Ess\M2ePro\Model\Amazon\Template\ProductType::FIELD_CUSTOM_VALUE:
                return $setting['value'];
            case \Ess\M2ePro\Model\Amazon\Template\ProductType::FIELD_CUSTOM_ATTRIBUTE:
                $magentoProduct = $this->getMagentoProduct();
                if (!$magentoProduct->exists()) {
                    return null;
                }

                return $magentoProduct->getAttributeValue($setting['attribute_code'], false);
        }

        return null;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData(): array
    {
        $listingProduct = $this->getListingProduct();
        $amazonListingProduct = $listingProduct->getChildObject();

        if ($amazonListingProduct->isGeneralIdOwner()) {
            $variationManager = $amazonListingProduct->getVariationManager();

            if (
                $variationManager->isRelationParentType()
                && !$this->isValidGeneralIdOwner($listingProduct)
            ) {
                return [];
            }

            if ($variationManager->isRelationChildType()) {
                $variationParent = $this->listingProductFactory
                    ->create()
                    ->load($variationManager->getVariationParentId());

                if (
                    !$variationParent->getId()
                    || !$this->isValidGeneralIdOwner($variationParent)
                ) {
                    return [];
                }
            }
        }

        $data = $this->getSpecifics();

        $listingProduct->getId();

        if (!$this->getVariationManager()->isRelationParentType()) {
            $data = array_merge(
                $data,
                $this->getGiftData()
            );
        }

        $data = array_merge($data, $this->getTaxCodeData(), $this->getConditionData());

        if (!$amazonListingProduct->isAfnChannel()) {
            $data = array_merge($data, $this->getShippingData());
        }

        return $data;
    }

    private function isValidGeneralIdOwner(\Ess\M2ePro\Model\Listing\Product $listingProduct): bool
    {
        $additionalData = $listingProduct->getAdditionalData();
        if (
            empty($additionalData['variation_channel_theme'])
            || empty($additionalData['variation_matched_attributes'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConditionData(): array
    {
        $condition = [];
        $listingSource = $this->getAmazonListingProduct()->getListingSource();

        $this->searchNotFoundAttributes();
        $condition['condition'] = $listingSource->getCondition();
        if ($condition['condition'] != \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW) {
            $condition['condition_note'] = $listingSource->getConditionNote();
        }

        $this->processNotFoundAttributes('Condition / Condition Note');

        return $condition;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getGiftData(): array
    {
        $giftWrap = $this->getAmazonListingProduct()->getListingSource()->getGiftWrap();
        $giftMessage = $this->getAmazonListingProduct()->getListingSource()->getGiftMessage();

        $isOnlineGiftSettingsDisabled = $this->getListingProduct()->getSetting(
            'additional_data',
            'online_gift_settings_disabled',
            true
        );

        if ($isOnlineGiftSettingsDisabled && $giftWrap === false && $giftMessage === false) {
            return [];
        }

        $data = [];

        if ($giftWrap !== null) {
            $data['gift_wrap'] = $giftWrap;
        }

        if ($giftMessage !== null) {
            $data['gift_message'] = $giftMessage;
        }

        return $data;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getShippingData(): array
    {
        if (
            $this->getAmazonListingProduct()->isAfnChannel()
            || !$this->getAmazonListingProduct()->isExistShippingTemplate()
            && !$this->getAmazonListing()->isExistShippingTemplate()
        ) {
            return [];
        }

        if (!$this->getAmazonListingProduct()->isExistShippingTemplate()) {
            return [
                'shipping_data' => [
                    'template_name' => $this->getAmazonListing()->getShippingTemplateSource(
                        $this->getAmazonListingProduct()->getActualMagentoProduct()
                    )->getTemplateId(),
                ],
            ];
        }

        return [
            'shipping_data' => [
                'template_name' => $this->getAmazonListingProduct()->getShippingTemplateSource()->getTemplateId(),
            ],
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getTaxCodeData(): array
    {
        if (
            !$this->getAmazonMarketplace()->isProductTaxCodePolicyAvailable()
            || !$this->getAmazonAccount()->isVatCalculationServiceEnabled()
        ) {
            return [];
        }

        if (!$this->getAmazonListingProduct()->isExistProductTaxCodeTemplate()) {
            return [];
        }

        $data = [];

        $this->searchNotFoundAttributes();

        $data['tax_code'] = $this->getAmazonListingProduct()->getProductTaxCodeTemplateSource()->getProductTaxCode();

        $this->processNotFoundAttributes('Product Tax Code');

        return $data;
    }
}
