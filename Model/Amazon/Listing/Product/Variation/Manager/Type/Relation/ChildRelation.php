<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation
 */
class ChildRelation extends \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\PhysicalUnit
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    private $parentListingProduct = null;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getParentListingProduct()
    {
        if ($this->parentListingProduct === null) {
            $parentListingProductId = $this->getVariationManager()->getVariationParentId();
            $this->parentListingProduct = $this->amazonFactory->getObjectLoaded(
                'Listing\Product',
                $parentListingProductId
            );
        }

        return $this->parentListingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    public function getAmazonParentListingProduct()
    {
        return $this->getParentListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
     */
    public function getParentTypeModel()
    {
        return $this->getAmazonParentListingProduct()->getVariationManager()->getTypeModel();
    }

    //########################################

    /**
     * @return array|mixed|null
     */
    public function getRealProductOptions()
    {
        $productOptions = $this->getProductOptions();

        $virtualProductAttributes = $this->getParentTypeModel()->getVirtualProductAttributes();
        if (empty($virtualProductAttributes)) {
            return $productOptions;
        }

        $realProductOptions = [];
        foreach ($productOptions as $attribute => $value) {
            if (isset($virtualProductAttributes[$attribute])) {
                continue;
            }

            $realProductOptions[$attribute] = $value;
        }

        return $realProductOptions;
    }

    //########################################

    /**
     * @return bool
     */
    public function isVariationChannelMatched()
    {
        return (bool)$this->getAmazonListingProduct()->getData('is_variation_channel_matched');
    }

    //########################################

    /**
     * @param array $options
     */
    public function setChannelVariation(array $options)
    {
        $this->unsetChannelVariation();

        $this->setChannelOptions($options, false);

        $amazonListingProduct = $this->getAmazonListingProduct();

        $amazonListingProduct->setData('is_variation_channel_matched', 1);

        $this->getListingProduct()->save();
    }

    public function unsetChannelVariation()
    {
        if (!$this->isVariationChannelMatched()) {
            return;
        }

        $this->setChannelOptions([], false);

        $amazonListingProduct = $this->getAmazonListingProduct();

        $amazonListingProduct->setData('is_variation_channel_matched', 0);

        $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @return mixed|null
     */
    public function getChannelOptions()
    {
        return $this->getListingProduct()->getSetting('additional_data', 'variation_channel_options', []);
    }

    /**
     * @return array|mixed|null
     */
    public function getRealChannelOptions()
    {
        $channelOptions = $this->getChannelOptions();

        $virtualChannelAttributes = $this->getParentTypeModel()->getVirtualChannelAttributes();
        if (empty($virtualChannelAttributes)) {
            return $channelOptions;
        }

        $realChannelOptions = [];
        foreach ($channelOptions as $attribute => $value) {
            if (isset($virtualChannelAttributes[$attribute])) {
                continue;
            }

            $realChannelOptions[$attribute] = $value;
        }

        return $realChannelOptions;
    }

    // ---------------------------------------

    private function setChannelOptions(array $options, $save = true)
    {
        $this->getListingProduct()->setSetting('additional_data', 'variation_channel_options', $options);
        $save && $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @param array $matchedAttributes
     * @param bool
     */
    public function setCorrectMatchedAttributes(array $matchedAttributes, $save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_correct_matched_attributes',
            $matchedAttributes
        );
        $save && $this->getListingProduct()->save();
    }

    /**
     * @return mixed
     */
    public function getCorrectMatchedAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_correct_matched_attributes',
            []
        );
    }

    // ---------------------------------------

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isActualMatchedAttributes()
    {
        $correctMatchedAttributes = $this->getCorrectMatchedAttributes();
        if (empty($correctMatchedAttributes)) {
            return true;
        }

        $parentTypeModel = $this->getAmazonParentListingProduct()->getVariationManager()->getTypeModel();
        $currentMatchedAttributes = $parentTypeModel->getMatchedAttributes();
        if (empty($currentMatchedAttributes)) {
            return false;
        }

        return count(array_diff_assoc($correctMatchedAttributes, $currentMatchedAttributes)) <= 0;
    }

    //########################################

    public function clearTypeData()
    {
        parent::clearTypeData();

        $this->unsetChannelVariation();

        $additionalData = $this->getListingProduct()->getAdditionalData();
        unset($additionalData['variation_channel_options']);
        unset($additionalData['variation_correct_matched_attributes']);
        $this->getListingProduct()->setSettings('additional_data', $additionalData);

        $this->getListingProduct()->save();
    }

    //########################################
}
