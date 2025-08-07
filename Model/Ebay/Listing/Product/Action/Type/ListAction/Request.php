<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction\Request
 */
class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    protected $isVerifyCall = false;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct(
            $componentEbayConfiguration,
            $activeRecordFactory,
            $ebayFactory,
            $helperFactory,
            $modelFactory
        );

        $this->moduleConfiguration = $moduleConfiguration;
    }

    protected function beforeBuildDataEvent()
    {
        if ($this->isVerifyCall) {
            parent::beforeBuildDataEvent();

            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        if ($this->getListingProduct()->getMagentoProduct()->isGroupedType()) {
            $additionalData['grouped_product_mode'] = $this->moduleConfiguration->getGroupedProductMode();
        }

        unset($additionalData['synch_template_list_rules_note']);
        unset($additionalData['item_duplicate_action_required']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getListingProduct()->getChildObject()->setData('is_duplicate', 0);

        $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        if (!$uuid = $this->getEbayListingProduct()->getItemUUID()) {
            $uuid = $this->getEbayListingProduct()->generateItemUUID();
            $this->getEbayListingProduct()->setData('item_uuid', $uuid)->save();
        }

        $generalData = $this->getGeneralData();
        $descriptionData = $this->getDescriptionData();
        $otherData = $this->getOtherData();

        if (
            isset($generalData['product_details'])
            || isset($descriptionData['product_details'])
            || isset($otherData['product_details'])
        ) {
            $generalData['product_details'] = array_merge(
                $generalData['product_details'] ?? [],
                $descriptionData['product_details'] ?? [],
                $otherData['product_details'] ?? []
            );
            unset($descriptionData['product_details']);
            unset($otherData['product_details']);
        }

        $data = array_merge(
            [
                'sku' => $this->getEbayListingProduct()->getSku(),
                'item_uuid' => $uuid,
            ],
            $generalData,
            $this->getQtyData(),
            $this->getPriceData(),
            $this->getTitleData(),
            $this->getSubtitleData(),
            $descriptionData,
            $this->getImagesData(),
            $this->getCategoriesData(),
            $this->getPartsData(),
            $this->getReturnData(),
            $this->getShippingData(),
            $this->getVariationsData(),
            $otherData
        );

        if ($this->isVerifyCall) {
            $data['verify_call'] = true;
        }

        return $data;
    }

    //########################################

    protected function initializeVariations()
    {
        parent::initializeVariations();

        if (!$this->getEbayListingProduct()->isVariationMode()) {
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();
        $additionalData['variations_that_can_not_be_deleted'] = [];
        $this->getListingProduct()->setSettings('additional_data', $additionalData)->save();

        $variations = $this->getListingProduct()->getVariations(true);

        foreach ($variations as $variation) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            if ($ebayVariation->isDelete()) {
                $variation->delete();
                continue;
            }

            $needSave = false;

            if ($ebayVariation->isAdd()) {
                $variation->setData('add', 0);
                $needSave = true;
            }

            if ($ebayVariation->isNotListed()) {
                $variation->setData('online_sku', null);
                $variation->setData('online_price', null);
                $variation->setData('online_qty', null);
                $variation->setData('online_qty_sold', null);

                $needSave = true;
            }

            $additionalData = $variation->getAdditionalData();
            if (!empty($additionalData['online_product_details'])) {
                unset($additionalData['online_product_details']);
                $variation->setSettings('additional_data', $additionalData);

                $needSave = true;
            }

            $needSave && $variation->save();
        }
    }

    //########################################

    protected function getIsEpsImagesMode()
    {
        return null;
    }

    //########################################

    protected function replaceVariationSpecificsNames(array $data)
    {
        if (
            !$this->getIsVariationItem() || !$this->getMagentoProduct()->isConfigurableType() ||
            empty($data['variations_sets']) || !is_array($data['variations_sets'])
        ) {
            return $data;
        }

        $confAttributes = [];
        $additionalData = $this->getListingProduct()->getAdditionalData();
        if (!empty($additionalData['configurable_attributes'])) {
            $confAttributes = $additionalData['configurable_attributes'];
        }

        if (empty($confAttributes)) {
            return $data;
        }

        $replacements = [];

        foreach ($this->getEbayListingProduct()->getCategoryTemplate()->getSpecifics(true) as $specific) {
            if (!$specific->isItemSpecificsMode() || !$specific->isCustomAttributeValueMode()) {
                continue;
            }

            $attrCode = trim($specific->getData('value_custom_attribute'));
            $attrTitle = trim($specific->getData('attribute_title'));

            if (!array_key_exists($attrCode, $confAttributes) || $confAttributes[$attrCode] == $attrTitle) {
                continue;
            }

            $replacements[$confAttributes[$attrCode]] = $attrTitle;
        }

        if (empty($replacements)) {
            return $data;
        }

        $data = $this->doReplaceVariationSpecifics($data, $replacements);
        $this->addMetaData('variations_specifics_replacements', $replacements);

        return $data;
    }

    protected function resolveVariationMpnIssue(array $data)
    {
        if (!$this->getIsVariationItem()) {
            return $data;
        }

        $data['without_mpn_variation_issue'] = true;

        return $data;
    }

    //########################################

    public function setIsVerifyCall($value)
    {
        $this->isVerifyCall = $value;

        return $this;
    }

    //########################################
}
