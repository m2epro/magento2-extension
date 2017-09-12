<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    protected $isVerifyCall = false;

    //########################################

    protected function beforeBuildDataEvent()
    {
        if ($this->isVerifyCall) {
            parent::beforeBuildDataEvent();
            return;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        unset($additionalData['synch_template_list_rules_note']);
        unset($additionalData['add_to_schedule']);
        unset($additionalData['item_duplicate_action_required']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);

        $this->getListingProduct()->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK);
        $this->getListingProduct()->setData('synch_reasons', null);
        $this->getListingProduct()->getChildObject()->setData('is_duplicate', 0);

        $this->getListingProduct()->save();
    }

    protected function afterBuildDataEvent(array $data)
    {
        $this->getConfigurator()->setPriority(
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator::PRIORITY_LIST
        );

        parent::afterBuildDataEvent($data);
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

        $data = array_merge(

            array(
                'sku'       => $this->getEbayListingProduct()->getSku(),
                'item_uuid' => $uuid,
            ),

            $this->getRequestVariations()->getRequestData(),
            $this->getRequestCategories()->getRequestData(),

            $this->getRequestPayment()->getRequestData(),
            $this->getRequestReturn()->getRequestData(),
            $this->getRequestShipping()->getRequestData(),

            $this->getRequestSelling()->getRequestData(),
            $this->getRequestDescription()->getRequestData()
        );

        $this->isVerifyCall && $data['verify_call'] = true;
        return $data;
    }

    //########################################

    protected function initializeVariations()
    {
        if (!$this->getEbayListingProduct()->isVariationMode()) {
            foreach ($this->getListingProduct()->getVariations(true) as $variation) {
                $variation->delete();
            }
        }

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
            if (!empty($additionalData['ebay_mpn_value'])) {
                unset($additionalData['ebay_mpn_value']);
                $variation->setSettings('additional_data', $additionalData);

                $needSave = true;
            }

            $needSave && $variation->save();
        }
    }

    //########################################

    protected function getIsEpsImagesMode()
    {
        return NULL;
    }

    //########################################

    protected function replaceVariationSpecificsNames(array $data)
    {
        if (!$this->getIsVariationItem() || !$this->getMagentoProduct()->isConfigurableType() ||
            empty($data['variations_sets']) || !is_array($data['variations_sets'])) {

            return $data;
        }

        $confAttributes = array();
        $additionalData = $this->getListingProduct()->getAdditionalData();
        if (!empty($additionalData['configurable_attributes'])) {
            $confAttributes = $additionalData['configurable_attributes'];
        }

        if (empty($confAttributes)) {
            return $data;
        }

        $replacements = array();

        foreach ($this->getEbayListingProduct()->getCategoryTemplate()->getSpecifics(true) as $specific) {

            if (!$specific->isItemSpecificsMode() || !$specific->isCustomAttributeValueMode()) {
                continue;
            }

            $attrCode  = $specific->getData('value_custom_attribute');
            $attrTitle = $specific->getData('attribute_title');

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