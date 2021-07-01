<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

use \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General as DataBuilderGeneral;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation as EbayVariation;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing\Product\Variation;
use Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Resolver
 */
class Resolver extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    const MPN_SPECIFIC_NAME = 'MPN';

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;
    protected $isAllowedToSave = false;

    protected $isAllowedToProcessVariationsWhichAreNotExistInTheModule = false;
    protected $isAllowedToProcessVariationMpnErrors                    = false;
    protected $isAllowedToProcessExistedVariations                     = false;

    protected $moduleVariations  = [];
    protected $channelVariations = [];

    protected $variationMpnValues = [];

    /** @var \Ess\M2ePro\Model\Response\Message\Set */
    protected $messagesSet;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        $this->parentFactory       = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function resolve()
    {
        try {
            $this->getMessagesSet()->clearEntities();
            $this->validate();

            $this->prepareModuleVariations();
            $this->validateModuleVariations();

            $this->prepareChannelVariations();

            $this->processVariationsWhichAreNotExistInTheModule();
            $this->processVariationMpnErrors();

            $this->processExistedVariations();
        } catch (\Exception $exception) {
            $message = $this->modelFactory->getObject('Response\Message');
            $message->initFromException($exception);

            $this->getMessagesSet()->addEntity($message);
        }
    }

    //########################################

    private function validate()
    {
        if (!($this->listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf(
                    'Listing product is not provided [%s].',
                    get_class($this->listingProduct)
                )
            );
        }

        if (!$this->listingProduct->getChildObject()->isVariationsReady()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not a variation product.');
        }

        if (!$this->listingProduct->isRevisable()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not a revisable product.');
        }

        return true;
    }

    private function validateModuleVariations()
    {
        $skus    = [];
        $options = [];

        $duplicatedSkus    = [];
        $duplicatedOptions = [];

        foreach ($this->moduleVariations as $variation) {
            $sku    = $variation['sku'];
            $option = $this->getVariationHash($variation);

            if (empty($sku)) {
                continue;
            }

            if (in_array($sku, $skus)) {
                $duplicatedSkus[] = $sku;
            } else {
                $skus[] = $sku;
            }

            if (in_array($option, $options)) {
                $duplicatedOptions[] = $option;
            } else {
                $options[] = $option;
            }
        }

        if (!empty($duplicatedSkus)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf(
                    'Duplicated SKUs: %s',
                    implode(',', $duplicatedSkus)
                )
            );
        }

        if (!empty($duplicatedOptions)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf(
                    'Duplicated Options: %s',
                    implode(',', $duplicatedOptions)
                )
            );
        }
    }

    //########################################

    private function prepareModuleVariations()
    {
        $variationUpdater = $this->modelFactory->getObject('Ebay_Listing_Product_Variation_Updater');
        $variationUpdater->process($this->listingProduct);

        $trimmedSpecificsReplacements = [];
        $specificsReplacements        = $this->listingProduct->getSetting(
            'additional_data',
            'variations_specifics_replacements',
            []
        );

        foreach ($specificsReplacements as $findIt => $replaceBy) {
            $trimmedSpecificsReplacements[trim($findIt)] = trim($replaceBy);
        }

        $this->moduleVariations = [];
        foreach ($this->listingProduct->getVariations(true) as $variation) {

            /**@var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            $tempVariation = [
                'id'            => $variation->getId(),
                'sku'           => $ebayVariation->getOnlineSku(),
                'price'         => $ebayVariation->getOnlinePrice(),
                'quantity'      => $ebayVariation->getOnlineQty(),
                'quantity_sold' => $ebayVariation->getOnlineQtySold(),
                'specifics'     => [],
                'details'       => []
            ];

            //--------------------------------
            foreach ($variation->getOptions(true) as $option) {
                /**@var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

                $optionName  = trim($option->getAttribute());
                $optionValue = trim($option->getOption());

                if (array_key_exists($optionName, $trimmedSpecificsReplacements)) {
                    $optionName = $trimmedSpecificsReplacements[$optionName];
                }

                $tempVariation['specifics'][$optionName] = $optionValue;
            }

            $this->insertVariationDetails($variation, $tempVariation);
            //--------------------------------

            //-- MPN Specific has been changed
            if (!empty($tempVariation['details']['mpn_previous']) && !empty($tempVariation['details']['mpn']) &&
                $tempVariation['details']['mpn_previous'] != $tempVariation['details']['mpn']) {
                $oneMoreVariation                   = [
                    'id'        => null,
                    'qty'       => 0,
                    'price'     => $tempVariation['price'],
                    'sku'       => 'del-' . sha1(microtime(1) . $tempVariation['sku']),
                    'add'       => 0,
                    'delete'    => 1,
                    'specifics' => $tempVariation['specifics'],
                    'details'   => $tempVariation['details'],
                    'has_sales' => true,
                ];
                $oneMoreVariation['details']['mpn'] = $tempVariation['details']['mpn_previous'];

                if (!empty($trimmedSpecificsReplacements)) {
                    $oneMoreVariation['variations_specifics_replacements'] = $trimmedSpecificsReplacements;
                }

                $this->moduleVariations[] = $oneMoreVariation;
            }
            unset($tempVariation['details']['mpn_previous']);
            //--------------------------------

            $this->moduleVariations[] = $tempVariation;
        }

        //--------------------------------
        $variationsThatCanNoBeDeleted = $this->listingProduct->getSetting(
            'additional_data',
            'variations_that_can_not_be_deleted',
            []
        );

        foreach ($variationsThatCanNoBeDeleted as $canNoBeDeleted) {
            $this->moduleVariations[] = [
                'id'            => null,
                'sku'           => $canNoBeDeleted['sku'],
                'price'         => isset($canNoBeDeleted['price']) ? $canNoBeDeleted['price'] : 0,
                'quantity'      => $canNoBeDeleted['qty'],
                'quantity_sold' => $canNoBeDeleted['qty'],
                'specifics'     => $canNoBeDeleted['specifics'],
                'details'       => isset($canNoBeDeleted['details']) ? $canNoBeDeleted['details'] : []
            ];
        }
    }

    private function insertVariationDetails(\Ess\M2ePro\Model\Listing\Product\Variation $variation, &$tempVariation)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct      = $this->listingProduct->getChildObject();
        $ebayDescriptionTemplate = $ebayListingProduct->getEbayDescriptionTemplate();

        $additionalData = $variation->getAdditionalData();

        foreach (['isbn', 'upc', 'ean', 'mpn', 'epid'] as $tempType) {
            if ($tempType == 'mpn' && !empty($additionalData['online_product_details']['mpn'])) {
                if ($variation->getListingProduct()
                        ->getSetting('additional_data', 'is_variation_mpn_filled') === false) {
                    continue;
                }

                $tempVariation['details']['mpn'] = $additionalData['online_product_details']['mpn'];

                $isMpnCanBeChanged = $this->getHelper('Component_Ebay_Configuration')
                    ->getVariationMpnCanBeChanged();

                if (!$isMpnCanBeChanged) {
                    continue;
                }

                $tempVariation['details']['mpn_previous'] = $additionalData['online_product_details']['mpn'];
            }

            if (isset($additionalData['product_details'][$tempType])) {
                $tempVariation['details'][$tempType] = $additionalData['product_details'][$tempType];
                continue;
            }

            if ($tempType == 'mpn') {
                if ($ebayDescriptionTemplate->isProductDetailsModeNone('brand')) {
                    continue;
                }

                if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply('brand')) {
                    $tempVariation['details'][$tempType] = DataBuilderGeneral::PRODUCT_DETAILS_DOES_NOT_APPLY;
                    continue;
                }
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply($tempType)) {
                $tempVariation['details'][$tempType] = DataBuilderGeneral::PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            if (!$this->listingProduct->getMagentoProduct()->isConfigurableType() &&
                !$this->listingProduct->getMagentoProduct()->isGroupedType()) {
                continue;
            }

            $attribute = $ebayDescriptionTemplate->getProductDetailAttribute($tempType);
            if (!$attribute) {
                continue;
            }

            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
            $options = $variation->getOptions(true);
            $option  = reset($options);

            $tempValue = $option->getMagentoProduct()->getAttributeValue($attribute);
            if (!$tempValue) {
                continue;
            }

            $tempVariation['details'][$tempType] = $tempValue;
        }

        $this->deleteNotAllowedIdentifiers($tempVariation['details']);
    }

    private function deleteNotAllowedIdentifiers(array &$data)
    {
        if (empty($data)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $this->listingProduct->getChildObject();

        $categoryId    = $ebayListingProduct->getCategoryTemplateSource()->getCategoryId();
        $marketplaceId = $this->listingProduct->getMarketplace()->getId();

        $categoryFeatures = $this->getHelper('Component_Ebay_Category_Ebay')
            ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return;
        }

        $statusDisabled = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean', 'upc', 'isbn', 'epid'] as $identifier) {
            $key = $identifier . '_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);
            }
        }
    }

    /**
     * @throws Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function prepareChannelVariations()
    {
        $this->channelVariations  = [];
        $this->variationMpnValues = [];

        foreach ($this->getVariationsDataFromEbay() as $variation) {
            $tempVariation = [
                'id'            => null,
                'sku'           => $variation['sku'],
                'price'         => $variation['price'],
                'quantity'      => $variation['quantity'],
                'quantity_sold' => $variation['quantity_sold'],
                'specifics'     => $variation['specifics'],
                'details'       => !empty($variation['details']) ? $variation['details'] : []
            ];

            if (isset($tempVariation['specifics'][self::MPN_SPECIFIC_NAME])) {
                $tempVariation['details']['mpn'] = $tempVariation['specifics'][self::MPN_SPECIFIC_NAME];

                $this->variationMpnValues[] = [
                    'mpn'       => $tempVariation['specifics'][self::MPN_SPECIFIC_NAME],
                    'sku'       => $variation['sku'],
                    'specifics' => $variation['specifics'],
                ];

                unset($tempVariation['specifics'][self::MPN_SPECIFIC_NAME]);
            }

            $this->channelVariations[] = $tempVariation;
        }
    }

    /**
     * @return array
     * @throws Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function getVariationsDataFromEbay()
    {
        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $connector */
        $connector = $this->modelFactory->getObject('Ebay_Connector_Dispatcher')->getVirtualConnector(
            'item',
            'get',
            'info',
            [
                'item_id'              => $this->listingProduct->getChildObject()->getEbayItemIdReal(),
                'parser_type'          => 'standard',
                'full_variations_mode' => true
            ],
            'result',
            $this->listingProduct->getMarketplace(),
            $this->listingProduct->getAccount()
        );

        $connector->process();
        $result = $connector->getResponseData();

        if (empty($result['variations'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Unable to retrieve variations from channel.');
        }

        return $result['variations'];
    }

    //########################################

    private function getVariationsWhichDoNotExistInModule()
    {
        $variations = [];

        foreach ($this->channelVariations as $channelVariation) {
            foreach ($this->moduleVariations as $moduleVariation) {
                if ($this->isVariationEqualWithCurrent($channelVariation, $moduleVariation)) {
                    continue 2;
                }
            }
            $variations[] = $channelVariation;
        }

        return $variations;
    }

    private function processExistedVariations()
    {
        if (!$this->isAllowedToProcessExistedVariations) {
            return;
        }

        foreach ($this->moduleVariations as $moduleVariation) {
            foreach ($this->channelVariations as $channelVariation) {
                if ($this->isVariationEqualWithCurrent($channelVariation, $moduleVariation)) {
                    $this->addNotice(
                        sprintf(
                            "Variation ID %s has been Updated. Hash: %s",
                            $moduleVariation['id'],
                            $this->getVariationHash($moduleVariation)
                        )
                    );

                    if (!$this->isAllowedToSave) {
                        continue;
                    }

                    $availableQty = ($channelVariation['quantity'] - $channelVariation['quantity_sold']);

                    /** @var \Ess\M2ePro\Model\Listing\Product\Variation $lpv */
                    $lpv = $this->parentFactory->getObjectLoaded(
                        \Ess\M2ePro\Helper\Component\Ebay::NICK,
                        'Listing_Product_Variation',
                        $moduleVariation['id']
                    );

                    $additionalData                           = $lpv->getAdditionalData();
                    $additionalData['online_product_details'] = $channelVariation['details'];

                    $lpv->addData(
                        [
                            'additional_data' => json_encode($additionalData)
                        ]
                    );
                    $lpv->save();

                    $lpv->getChildObject()->addData(
                        [
                            'online_sku'      => $channelVariation['sku'],
                            'online_qty'      => $channelVariation['quantity'],
                            'online_qty_sold' => $channelVariation['quantity_sold'],
                            'status'          => $availableQty > 0 ? \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
                                : \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
                            'add'             => 0,
                            'detele'          => 0,
                        ]
                    );
                    $lpv->getChildObject()->save();

                    continue 2;
                }
            }
        }
    }

    //########################################

    /**
     * variations_that_can_not_be_deleted will be filled up
     */
    private function processVariationsWhichAreNotExistInTheModule()
    {
        if (!$this->isAllowedToProcessVariationsWhichAreNotExistInTheModule) {
            return;
        }

        $variations = $this->getVariationsWhichDoNotExistInModule();
        if (empty($variations)) {
            return;
        }

        foreach ($variations as $variation) {
            $this->addWarning(
                sprintf(
                    "SKU %s has been added to the Module. Hash: %s",
                    $variation['sku'],
                    $this->getVariationHash($variation)
                )
            );
        }

        if (!$this->isAllowedToSave) {
            return;
        }

        $variationsThatCanNoBeDeleted = $this->listingProduct->getSetting(
            'additional_data',
            'variations_that_can_not_be_deleted',
            []
        );

        foreach ($variations as $variation) {
            $variationsThatCanNoBeDeleted[] = [
                'qty'       => 0,
                'price'     => $variation['price'],
                'sku'       => !empty($variation['sku']) ? 'del-' . sha1(microtime(1) . $variation['sku']) : '',
                'add'       => 0,
                'delete'    => 1,
                'specifics' => $variation['specifics'],
                'details'   => $variation['details'],
                'has_sales' => true,
            ];
        }

        $this->listingProduct->setSetting(
            'additional_data',
            'variations_that_can_not_be_deleted',
            $variationsThatCanNoBeDeleted
        );
        $this->listingProduct->save();
    }

    /**
     * @throws Logic
     */
    private function processVariationMpnErrors()
    {
        if (!$this->isAllowedToProcessVariationMpnErrors) {
            return;
        }

        $isVariationMpnFilled = !empty($this->variationMpnValues);

        $isVariationMpnFilled && $this->fillVariationMpnValues();

        if ($this->isAllowedToSave) {
            $this->listingProduct->setSetting('additional_data', 'is_variation_mpn_filled', $isVariationMpnFilled);

            if (!$isVariationMpnFilled) {
                $this->listingProduct->setSetting('additional_data', 'without_mpn_variation_issue', true);
            }

            $this->listingProduct->save();
        }
    }

    /**
     * @throws Logic
     */
    private function fillVariationMpnValues()
    {
        /** @var Collection $variationCollection */
        $variationCollection = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getCollection();
        $variationCollection->addFieldToFilter('listing_product_id', $this->listingProduct->getId());

        /** @var Collection $variationOptionCollection */
        $variationOptionCollection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
            ->getCollection();
        $variationOptionCollection->addFieldToFilter(
            'listing_product_variation_id',
            $variationCollection->getColumnValues('id')
        );

        /** @var Variation[] $variations */
        $variations = $variationCollection->getItems();

        /** @var Variation\Option[] $variationOptions */
        $variationOptions = $variationOptionCollection->getItems();

        foreach ($variations as $variation) {
            $specifics = [];

            foreach ($variationOptions as $id => $variationOption) {
                if ($variationOption->getListingProductVariationId() != $variation->getId()) {
                    continue;
                }

                $specifics[$variationOption->getAttribute()] = $variationOption->getOption();
                unset($variationOptions[$id]);
            }

            /** @var EbayVariation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            foreach ($this->variationMpnValues as $id => $variationMpnValue) {
                if ($ebayVariation->getOnlineSku() != $variationMpnValue['sku'] &&
                    $specifics != $variationMpnValue['specifics']
                ) {
                    continue;
                }

                $additionalData = $variation->getAdditionalData();

                if (!isset($additionalData['online_product_details']['mpn']) ||
                    $additionalData['online_product_details']['mpn'] != $variationMpnValue['mpn']
                ) {

                    $this->addWarning(
                        sprintf(
                            "MPN for SKU %s has been added to the Module. Hash: %s",
                            $variationMpnValue['sku'],
                            $this->getVariationHash($variation)
                        )
                    );

                    if (!$this->isAllowedToSave) {
                        continue;
                    }

                    $additionalData['online_product_details']['mpn'] = $variationMpnValue['mpn'];

                    $variation->setSettings('additional_data', $additionalData);
                    $variation->save();
                }

                unset($this->variationMpnValues[$id]);

                break;
            }
        }
    }

    //########################################

    private function isVariationEqualWithCurrent(array $channelVariation, array $moduleVariation)
    {
        if (count($channelVariation['specifics']) != count($moduleVariation['specifics'])) {
            return false;
        }

        $channelMpn = isset($channelVariation['details']['mpn']) ? $channelVariation['details']['mpn'] : null;
        $moduleMpn  = isset($moduleVariation['details']['mpn']) ? $moduleVariation['details']['mpn'] : null;

        if ($channelMpn != $moduleMpn) {
            return false;
        }

        foreach ($moduleVariation['specifics'] as $moduleVariationOptionName => $moduleVariationOptionValue) {
            $haveOption = false;
            foreach ($channelVariation['specifics'] as $channelVariationOptionName => $channelVariationOptionValue) {
                if (trim($moduleVariationOptionName) == trim($channelVariationOptionName) &&
                    trim($moduleVariationOptionValue) == trim($channelVariationOptionValue)) {
                    $haveOption = true;
                    break;
                }
            }

            if ($haveOption === false) {
                return false;
            }
        }

        return true;
    }

    private function getVariationHash($variation)
    {
        $hash = [];

        foreach ($variation['specifics'] as $name => $value) {
            $hash[] = trim($name) . '-' . trim($value);
        }

        if (!empty($variation['details']['mpn'])) {
            $hash[] = 'MPN' . '-' . $variation['details']['mpn'];
        }

        return implode('##', $hash);
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    public function setIsAllowedToSave($value)
    {
        $this->isAllowedToSave = $value;

        return $this;
    }

    public function setIsAllowedToProcessVariationsWhichAreNotExistInTheModule($value)
    {
        $this->isAllowedToProcessVariationsWhichAreNotExistInTheModule = $value;

        return $this;
    }

    public function setIsAllowedToProcessVariationMpnErrors($value)
    {
        $this->isAllowedToProcessVariationMpnErrors = $value;

        return $this;
    }

    public function setIsAllowedToProcessExistedVariations($value)
    {
        $this->isAllowedToProcessExistedVariations = $value;

        return $this;
    }

    public function getMessagesSet()
    {
        if ($this->messagesSet === null) {
            $this->messagesSet = $this->modelFactory->getObject('Response_Message_Set');
        }

        return $this->messagesSet;
    }

    //########################################

    protected function addError($messageText)
    {
        $message = $this->modelFactory->getObject('Response\Message');
        $message->initFromPreparedData($messageText, $message::TYPE_ERROR);

        $this->getMessagesSet()->addEntity($message);
    }

    protected function addWarning($messageText)
    {
        $message = $this->modelFactory->getObject('Response\Message');
        $message->initFromPreparedData($messageText, $message::TYPE_WARNING);

        $this->getMessagesSet()->addEntity($message);
    }

    protected function addNotice($messageText)
    {
        $message = $this->modelFactory->getObject('Response\Message');
        $message->initFromPreparedData($messageText, $message::TYPE_NOTICE);

        $this->getMessagesSet()->addEntity($message);
    }

    //########################################
}
