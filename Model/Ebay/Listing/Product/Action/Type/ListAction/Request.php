<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        return array_merge(

            array(
                'sku' => $this->getEbayListingProduct()->getSku()
            ),

            $this->getRequestVariations()->getRequestData(),
            $this->getRequestCategories()->getRequestData(),

            $this->getRequestPayment()->getRequestData(),
            $this->getRequestReturn()->getRequestData(),
            $this->getRequestShipping()->getRequestData(),

            $this->getRequestSelling()->getRequestData(),
            $this->getRequestDescription()->getRequestData()
        );
    }

    //########################################

    public function resetVariations()
    {
        $this->initializeVariations();

        $variations = $this->getListingProduct()->getVariations(true);

        foreach ($variations as $variation) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();

            if ($ebayVariation->isDelete() || !$this->getIsVariationItem()) {
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

    public function getTheSameProductAlreadyListed()
    {
        $config = $this->moduleConfig
            ->getGroupValue('/ebay/connector/listing/', 'check_the_same_product_already_listed');

        if (!is_null($config) && $config != 1) {
            return NULL;
        }

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $listingProductCollection
            ->getSelect()
            ->join(array('l'=>$listingTable),'`main_table`.`listing_id` = `l`.`id`',array());

        $listingProductCollection
            ->addFieldToFilter('status',array('neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED))
            ->addFieldToFilter('product_id',$this->getListingProduct()->getProductId())
            ->addFieldToFilter('account_id',$this->getAccount()->getId())
            ->addFieldToFilter('marketplace_id',$this->getMarketplace()->getId());

        $theSameListingProduct = $listingProductCollection->getFirstItem();

        if (!$theSameListingProduct->getId()) {
            return NULL;
        }

        return $theSameListingProduct;
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

        $data['variations_specifics_replacements'] = $replacements;

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
}