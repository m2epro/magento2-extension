<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    public const LIST_TYPE_EXIST = 'exist';
    public const LIST_TYPE_NEW = 'new';

    public const PARENTAGE_PARENT = 'parent';
    public const PARENTAGE_CHILD = 'child';

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->moduleConfiguration = $moduleConfiguration;
    }

    protected function beforeBuildDataEvent()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getListingProduct()->getMagentoProduct()->isGroupedType()) {
            $additionalData['grouped_product_mode'] = $this->moduleConfiguration->getGroupedProductMode();
        }

        unset($additionalData['synch_template_list_rules_note']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getListingProduct()->save();

        parent::beforeBuildDataEvent();
    }

    protected function getActionData()
    {
        $data = [
            'sku' => $this->cachedData['sku'],
            'type_mode' => $this->cachedData['list_type'],
        ];

        if ($this->cachedData['list_type'] == self::LIST_TYPE_NEW && $this->getVariationManager()->isRelationMode()) {
            $data = array_merge($data, $this->getRelationData());
        }

        $data = array_merge(
            $data,
            $this->getQtyData(),
            $this->getRegularPriceData(),
            $this->getBusinessPriceData(),
            $this->getDetailsData()
        );

        if ($this->getVariationManager()->isRelationParentType()) {
            return $data;
        }

        $productIdentifierData = $this->cachedData['list_type'] == self::LIST_TYPE_NEW
            ? $this->getNewProductIdentifierData()
            : $this->getExistProductIdentifierData();

        return array_merge($data, $productIdentifierData);
    }

    protected function prepareFinalData(array $data)
    {
        // Don`t sending amazon list price on "List Action",
        // but this data saved in metaData and use for adding instruction.
        if (isset($data['list_price'])) {
            unset($data['list_price']);
        }

        return parent::prepareFinalData($data);
    }

    private function getExistProductIdentifierData()
    {
        return [
            'product_id' => $this->cachedData['general_id'],
            'product_id_type' => Identifier::isISBN($this->cachedData['general_id'])
                ? Identifier::ISBN : Identifier::ASIN,
        ];
    }

    private function getNewProductIdentifierData()
    {
        $productIdentifiers = $this->getAmazonListingProduct()->getIdentifiers();
        $data = [];

        if ($worldwideId = $productIdentifiers->getWorldwideId()) {
            $data['product_id'] = $worldwideId->getIdentifier();
            $data['product_id_type'] = $worldwideId->isUPC() ? Identifier::UPC : Identifier::EAN;
        }

        return $data;
    }

    private function getRelationData()
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return [];
        }

        $productTypeTemplate = $this->getAmazonListingProduct()->getProductTypeTemplate();

        $data = [
            'product_type_nick' => $productTypeTemplate->getNick(),
            'variation_data' => [
                'theme' => $this->getChannelTheme(),
            ],
        ];

        if ($this->getVariationManager()->isRelationParentType()) {
            $data['variation_data']['parentage'] = self::PARENTAGE_PARENT;

            return $data;
        }

        $typeModel = $this->getVariationManager()->getTypeModel();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
        $parentAmazonListingProduct = $typeModel->getParentListingProduct()->getChildObject();

        $matchedAttributes = $parentAmazonListingProduct->getVariationManager()
                                                        ->getTypeModel()
                                                        ->getMatchedAttributes();

        $virtualChannelAttributes = $typeModel->getParentTypeModel()->getVirtualChannelAttributes();

        $attributes = [];
        foreach ($typeModel->getProductOptions() as $attribute => $value) {
            if (isset($virtualChannelAttributes[$attribute])) {
                continue;
            }

            $attributes[$matchedAttributes[$attribute]] = $value;
        }

        $data['variation_data'] = array_merge($data['variation_data'], [
            'parentage' => self::PARENTAGE_CHILD,
            'parent_sku' => $parentAmazonListingProduct->getSku(),
            'attributes' => $attributes,
        ]);

        return $data;
    }

    private function getChannelTheme()
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return null;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            return $this->getVariationManager()->getTypeModel()->getChannelTheme();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $parentVariationManager */
        $parentVariationManager = $this->getVariationManager()
                                       ->getTypeModel()
                                       ->getParentListingProduct()
                                       ->getChildObject()
                                       ->getVariationManager();

        return $parentVariationManager->getTypeModel()->getChannelTheme();
    }
}
