<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager;

class PhysicalUnit extends \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\AbstractModel
{
    //########################################

    /**
     * @return bool
     */
    public function isVariationProductMatched()
    {
        return (bool)(int)$this->getAmazonListingProduct()->getData('is_variation_product_matched');
    }

    //########################################

    /**
     * @return bool
     */
    public function isActualProductAttributes()
    {
        $productAttributes = array_map('strtolower', array_keys($this->getProductOptions()));
        $magentoAttributes = array_map('strtolower', $this->getMagentoAttributes());

        sort($productAttributes);
        sort($magentoAttributes);

        return $productAttributes == $magentoAttributes;
    }

    /**
     * @return bool
     */
    public function isActualProductVariation()
    {
        $currentOptions = $this->getProductOptions();

        $currentOptions = array_change_key_case(array_map('strtolower',$currentOptions), CASE_LOWER);
        $magentoVariations = $this->getListingProduct()->getMagentoProduct()
                                                       ->getVariationInstance()
                                                       ->getVariationsTypeStandard();

        foreach ($magentoVariations['variations'] as $magentoVariation) {

            $magentoOptions = array();

            foreach ($magentoVariation as $magentoOption) {
                $magentoOptions[strtolower($magentoOption['attribute'])] = strtolower($magentoOption['option']);
            }

            if (empty($magentoOptions)) {
                continue;
            }

            if ($currentOptions == $magentoOptions) {
                return true;
            }
        }

        return false;
    }

    //########################################

    /**
     * @param array $variation
     */
    public function setProductVariation(array $variation)
    {
        $this->unsetProductVariation();

        $this->createStructure($variation);

        $options = array();
        foreach ($variation as $option) {
            $options[$option['attribute']] = $option['option'];
        }

        $this->setProductOptions($options, false);

        $amazonListingProduct = $this->getAmazonListingProduct();

        $amazonListingProduct->setData('is_variation_product_matched',1);

        if ($this->getListingProduct()->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            $this->createChannelItem($options);
        }

        $this->getListingProduct()->save();
    }

    public function resetProductVariation()
    {
        if ($this->isVariationProductMatched()) {
            $this->unsetProductVariation();
        } else {
            $this->resetProductOptions();
        }
    }

    public function unsetProductVariation()
    {
        if (!$this->isVariationProductMatched()) {
            return;
        }

        $this->resetProductOptions(false);

        $amazonListingProduct = $this->getAmazonListingProduct();

        $amazonListingProduct->setData('is_variation_product_matched', 0);

        if ($this->getListingProduct()->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            $this->removeChannelItems();
        }

        $this->getListingProduct()->save();

        $this->removeStructure();
    }

    //########################################

    public function getProductOptions()
    {
        $productOptions = $this->getListingProduct()->getSetting('additional_data', 'variation_product_options', null);
        if (empty($productOptions)) {
            return NULL;
        }

        return $productOptions;
    }

    // ---------------------------------------

    private function setProductOptions(array $options, $save = true)
    {
        $this->getListingProduct()->setSetting('additional_data', 'variation_product_options', $options);
        $save && $this->getListingProduct()->save();
    }

    private function resetProductOptions($save = true)
    {
        $options = array_fill_keys($this->getMagentoAttributes(), null);
        $this->setProductOptions($options, $save);
    }

    //########################################

    public function clearTypeData()
    {
        $this->unsetProductVariation();

        $additionalData = $this->getListingProduct()->getAdditionalData();
        unset($additionalData['variation_product_options']);
        $this->getListingProduct()->setSettings('additional_data', $additionalData);

        $this->getListingProduct()->save();
    }

    public function inspectAndFixProductOptionsIds()
    {
        $currentVariations = $this->getListingProduct()->getVariations(true);

        if (empty($currentVariations)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Variation $currentVariation */
        $currentVariation = reset($currentVariations);

        $productOptions = array();
        foreach ($currentVariation->getOptions(true) as $currentOption) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $currentOption */
            $productOptions[$currentOption->getAttribute()] = $currentOption->getOption();
        }

        $magentoVariation = $this->getMagentoProduct()
                                 ->getVariationInstance()
                                 ->getVariationTypeStandard($productOptions);

        if (!is_array($magentoVariation)) {
            return;
        }

        foreach ($currentVariation->getOptions(true) as $currentOption) {
            foreach ($magentoVariation as $magentoOption) {

                if ($currentOption->getAttribute() != $magentoOption['attribute'] ||
                    $currentOption->getOption() != $magentoOption['option']) {
                    continue;
                }

                if ($currentOption->getProductId() == $magentoOption['product_id']) {
                    continue;
                }

                $currentOption->setData('product_id', $magentoOption['product_id']);
                $currentOption->save();
            }
        }
    }

    //########################################

    private function removeStructure()
    {
        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation->delete();
        }
    }

    private function createStructure(array $variation)
    {
        $variationId = $this->amazonFactory->getObject('Listing\Product\Variation')->addData(array(
            'listing_product_id' => $this->getListingProduct()->getId()
        ))->save()->getId();

        foreach ($variation as $option) {

            $tempData = array(
                'listing_product_variation_id' => $variationId,
                'product_id'   => $option['product_id'],
                'product_type' => $option['product_type'],
                'attribute'    => $option['attribute'],
                'option'       => $option['option']
            );

            $this->amazonFactory->getObject('Listing\Product\Variation\Option')->addData($tempData)->save();
        }
    }

    // ---------------------------------------

    private function removeChannelItems()
    {
        $items = $this->activeRecordFactory->getObject('Amazon\Item')->getCollection()
                            ->addFieldToFilter('account_id',$this->getListing()->getAccountId())
                            ->addFieldToFilter('marketplace_id',$this->getListing()->getMarketplaceId())
                            ->addFieldToFilter('sku',$this->getAmazonListingProduct()->getSku())
                            ->addFieldToFilter('product_id',$this->getListingProduct()->getProductId())
                            ->addFieldToFilter('store_id',$this->getListing()->getStoreId())
                            ->getItems();

        foreach ($items as $item) {
            /* @var $item \Ess\M2ePro\Model\Amazon\Item */
            $item->delete();
        }
    }

    private function createChannelItem(array $options)
    {
        $data = array(
            'account_id' => (int)$this->getListing()->getAccountId(),
            'marketplace_id' => (int)$this->getListing()->getMarketplaceId(),
            'sku' => $this->getAmazonListingProduct()->getSku(),
            'product_id' => (int)$this->getListingProduct()->getProductId(),
            'store_id' => (int)$this->getListing()->getStoreId(),
            'variation_product_options' => $this->getHelper('Data')->jsonEncode($options),
        );

        $this->activeRecordFactory->getObject('Amazon\Item')->setData($data)->save();
    }

    //########################################

    protected function getMagentoAttributes()
    {
        $magentoVariations = $this->getListingProduct()
            ->getMagentoProduct()
            ->getVariationInstance()
            ->getVariationsTypeStandard();

        return array_keys($magentoVariations['set']);
    }

    //########################################
}