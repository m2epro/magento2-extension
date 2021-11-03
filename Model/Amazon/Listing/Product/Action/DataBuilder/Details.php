<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Details
 */
class Details extends AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description
     */
    protected $descriptionTemplate = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Definition
     */
    protected $definitionTemplate = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Definition\Source
     */
    protected $definitionSource = null;

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        $data = [];

        $this->getListingProduct()->getId();

        if (!$this->getVariationManager()->isRelationParentType()) {
            $data = array_merge(
                $data,
                $this->getConditionData(),
                $this->getGiftData()
            );
        }

        $data = array_merge($data, $this->getTaxCodeData());

        if (!$this->getAmazonListingProduct()->isAfnChannel()) {
            $data = array_merge($data, $this->getShippingData());
        }

        $isUseDescriptionTemplate = false;

        do {
            if (!$this->getAmazonListingProduct()->isExistDescriptionTemplate()) {
                break;
            }

            $variationManager = $this->getAmazonListingProduct()->getVariationManager();

            if (($variationManager->isRelationChildType() || $variationManager->isIndividualType()) &&
                ($this->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
                    $this->getMagentoProduct()->isBundleType() ||
                    $this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks())) {
                break;
            }

            $isUseDescriptionTemplate = true;
        } while (false);

        if (!$isUseDescriptionTemplate) {
            if (isset($data['gift_wrap']) || isset($data['gift_message']) || isset($data['shipping_data'])) {
                $data['description_data']['title'] = $this->getAmazonListingProduct()
                    ->getMagentoProduct()
                    ->getName();
            }

            return $data;
        }

        $data = array_merge($data, $this->getDescriptionData());

        $data['number_of_items']       = $this->getDefinitionSource()->getNumberOfItems();
        $data['item_package_quantity'] = $this->getDefinitionSource()->getItemPackageQuantity();

        $browsenodeId = $this->getDescriptionTemplate()->getBrowsenodeId();
        if (empty($browsenodeId)) {
            return $data;
        }

        // browsenode_id requires description_data
        $data['browsenode_id'] = $browsenodeId;

        return array_merge(
            $data,
            $this->getProductData()
        );
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConditionData()
    {
        $condition = [];

        $this->searchNotFoundAttributes();
        $condition['condition'] = $this->getAmazonListingProduct()->getListingSource()->getCondition();
        $this->processNotFoundAttributes('Condition');

        if ($condition['condition'] != \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW) {
            $this->searchNotFoundAttributes();
            $condition['condition_note'] = $this->getAmazonListingProduct()->getListingSource()->getConditionNote();
            $this->processNotFoundAttributes('Condition Note');
        }

        return $condition;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getGiftData()
    {
        $giftWrap    = $this->getAmazonListingProduct()->getListingSource()->getGiftWrap();
        $giftMessage = $this->getAmazonListingProduct()->getListingSource()->getGiftMessage();

        $isOnlineGiftSettingsDisabled = $this->getListingProduct()->getSetting(
            'additional_data', 'online_gift_settings_disabled', true
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

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getDescriptionData()
    {
        $source = $this->getDefinitionSource();

        $data = [
            'brand'                    => $source->getBrand(),
            'manufacturer'             => $source->getManufacturer(),
            'manufacturer_part_number' => $source->getManufacturerPartNumber(),
        ];

        $this->searchNotFoundAttributes();
        $data['title'] = $this->getDefinitionSource()->getTitle();
        $this->processNotFoundAttributes('Title');

        $this->searchNotFoundAttributes();
        $data['msrp_rrp'] = $this->getDefinitionSource()->getMsrpRrp($this->getListing()->getStoreId());
        $this->processNotFoundAttributes('MSRP / RRP');

        $this->searchNotFoundAttributes();
        $data['description'] = $this->getDefinitionSource()->getDescription();
        $this->processNotFoundAttributes('Description');

        $this->searchNotFoundAttributes();
        $data['bullet_points'] = $this->getDefinitionSource()->getBulletPoints();
        $this->processNotFoundAttributes('Bullet Points');

        $this->searchNotFoundAttributes();
        $data['search_terms'] = $this->getDefinitionSource()->getSearchTerms();
        $this->processNotFoundAttributes('Search Terms');

        $this->searchNotFoundAttributes();
        $data['target_audience'] = $this->getDefinitionSource()->getTargetAudience();
        $this->processNotFoundAttributes('Target Audience');

        $this->searchNotFoundAttributes();
        $data['item_dimensions_volume'] = $source->getItemDimensionsVolume();
        $this->processNotFoundAttributes('Product Dimensions Volume');

        $this->searchNotFoundAttributes();
        $data['item_dimensions_volume_unit_of_measure'] = $source->getItemDimensionsVolumeUnitOfMeasure();
        $this->processNotFoundAttributes('Product Dimensions Measure Units');

        $this->searchNotFoundAttributes();
        $data['item_dimensions_weight'] = $source->getItemDimensionsWeight();
        $this->processNotFoundAttributes('Product Dimensions Weight');

        $this->searchNotFoundAttributes();
        $data['item_dimensions_weight_unit_of_measure'] = $source->getItemDimensionsWeightUnitOfMeasure();
        $this->processNotFoundAttributes('Product Dimensions Weight Units');

        $this->searchNotFoundAttributes();
        $data['package_dimensions_volume'] = $source->getPackageDimensionsVolume();
        $this->processNotFoundAttributes('Package Dimensions Volume');

        $this->searchNotFoundAttributes();
        $data['package_dimensions_volume_unit_of_measure'] = $source->getPackageDimensionsVolumeUnitOfMeasure();
        $this->processNotFoundAttributes('Package Dimensions Measure Units');

        $this->searchNotFoundAttributes();
        $data['package_weight'] = $source->getPackageWeight();
        $this->processNotFoundAttributes('Package Weight');

        $this->searchNotFoundAttributes();
        $data['package_weight_unit_of_measure'] = $source->getPackageWeightUnitOfMeasure();
        $this->processNotFoundAttributes('Package Weight Units');

        $this->searchNotFoundAttributes();
        $data['shipping_weight'] = $source->getShippingWeight();
        $this->processNotFoundAttributes('Shipping Weight');

        $this->searchNotFoundAttributes();
        $data['shipping_weight_unit_of_measure'] = $source->getShippingWeightUnitOfMeasure();
        $this->processNotFoundAttributes('Shipping Weight Units');

        if ($data['package_weight'] === null || $data['package_weight'] === '' ||
            $data['package_weight_unit_of_measure'] === ''
        ) {
            unset(
                $data['package_weight'],
                $data['package_weight_unit_of_measure']
            );
        }

        if ($data['shipping_weight'] === null || $data['shipping_weight'] === '' ||
            $data['shipping_weight_unit_of_measure'] === ''
        ) {
            unset(
                $data['shipping_weight'],
                $data['shipping_weight_unit_of_measure']
            );
        }

        if (!$this->getVariationManager()->isRelationParentType()) {
            return ['description_data' => $data];
        }

        if (in_array('', $data['item_dimensions_volume']) || $data['item_dimensions_volume_unit_of_measure'] === '') {
            unset(
                $data['item_dimensions_volume'],
                $data['item_dimensions_volume_unit_of_measure']
            );
        }

        if ($data['item_dimensions_weight'] === '' || $data['item_dimensions_weight_unit_of_measure'] === '') {
            unset(
                $data['item_dimensions_weight'],
                $data['item_dimensions_weight_unit_of_measure']
            );
        }

        if (in_array('', $data['package_dimensions_volume']) ||
            $data['package_dimensions_volume_unit_of_measure'] === ''
        ) {
            unset(
                $data['package_dimensions_volume'],
                $data['package_dimensions_volume_unit_of_measure']
            );
        }

        return ['description_data' => $data];
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getProductData()
    {
        $data = [];

        $this->searchNotFoundAttributes();

        foreach ($this->getDescriptionTemplate()->getSpecifics(true) as $specific) {
            $source = $specific->getSource($this->getAmazonListingProduct()->getActualMagentoProduct());

            if (!$specific->isRequired() && !$specific->isModeNone()
                && ($source->getValue() === null || $source->getValue() === '' || $source->getValue() === [])) {
                continue;
            }

            $data = $this->getHelper('Data')->arrayReplaceRecursive(
                $data,
                (array)$this->getHelper('Data')->jsonDecode($source->getPath())
            );
        }

        $this->processNotFoundAttributes('Product Specifics');

        return [
            'product_data'      => $data,
            'product_data_nick' => $this->getDescriptionTemplate()->getProductDataNick(),
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getShippingData()
    {
        if ($this->getAmazonListingProduct()->isAfnChannel() ||
            !$this->getAmazonListingProduct()->isExistShippingTemplate() &&
            !$this->getAmazonListing()->isExistShippingTemplate()
        ) {
            return [];
        }

        if (!$this->getAmazonListingProduct()->isExistShippingTemplate()) {
            return [
                'shipping_data' => [
                    'template_name' => $this->getAmazonListing()->getShippingTemplateSource(
                        $this->getAmazonListingProduct()->getActualMagentoProduct()
                    )->getTemplateName(),
                ]
            ];
        }

        return [
            'shipping_data' => [
                'template_name' => $this->getAmazonListingProduct()->getShippingTemplateSource()->getTemplateName(),
            ]
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getTaxCodeData()
    {
        if (!$this->getAmazonMarketplace()->isProductTaxCodePolicyAvailable() ||
            !$this->getAmazonAccount()->isVatCalculationServiceEnabled()
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

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getDescriptionTemplate()
    {
        if ($this->descriptionTemplate === null) {
            $this->descriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
        }

        return $this->descriptionTemplate;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Definition
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getDefinitionTemplate()
    {
        if ($this->definitionTemplate === null) {
            $this->definitionTemplate = $this->getDescriptionTemplate()->getDefinitionTemplate();
        }

        return $this->definitionTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Definition\Source
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getDefinitionSource()
    {
        if ($this->definitionSource === null) {
            $this->definitionSource = $this->getDefinitionTemplate()
                ->getSource($this->getAmazonListingProduct()->getActualMagentoProduct());
        }

        return $this->definitionSource;
    }

    //########################################
}
