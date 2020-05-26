<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel
{
    const INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED = 'magento_product_promotions_data_changed';
    const INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED   = 'magento_product_lag_time_data_changed';
    const INSTRUCTION_TYPE_DETAILS_DATA_CHANGED    = 'magento_product_details_data_changed';

    //########################################

    public function getTrackingAttributes()
    {
        return array_unique(
            array_merge(
                $this->getLagTimeTrackingAttributes(),
                $this->getPromotionsTrackingAttributes(),
                $this->getDetailsTrackingAttributes()
            )
        );
    }

    public function getInstructionsDataByAttributes(array $attributes)
    {
        $data = [];

        if (array_intersect($attributes, $this->getLagTimeTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 40;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if (array_intersect($attributes, $this->getPromotionsTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 40;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if (array_intersect($attributes, $this->getDetailsTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }

    //########################################

    public function getLagTimeTrackingAttributes()
    {
        $walmartSellingFormatTemplate = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate();

        $trackingAttributes = array_merge(
            $walmartSellingFormatTemplate->getLagTimeAttributes()
        );

        return array_unique($trackingAttributes);
    }

    public function getPromotionsTrackingAttributes()
    {
        $trackingAttributes = [];

        $walmartSellingFormatTemplate = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate();

        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion[] $promotions */
        $promotions = $walmartSellingFormatTemplate->getPromotions(true);

        foreach ($promotions as $promotion) {
            $trackingAttributes = array_merge(
                $trackingAttributes,
                $promotion->getPriceAttributes(),
                $promotion->getComparisonPriceAttributes(),
                $promotion->getStartDateAttributes(),
                $promotion->getEndDateAttributes()
            );
        }

        return array_unique($trackingAttributes);
    }

    public function getDetailsTrackingAttributes()
    {
        $trackingAttributes = [];

        $walmartSellingFormatTemplate = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate();

        $trackingAttributes = array_merge(
            $trackingAttributes,
            $walmartSellingFormatTemplate->getSaleTimeStartDateAttributes(),
            $walmartSellingFormatTemplate->getSaleTimeEndDateAttributes(),
            $walmartSellingFormatTemplate->getMapPriceAttributes(),
            $walmartSellingFormatTemplate->getProductTaxCodeAttributes(),
            $walmartSellingFormatTemplate->getItemWeightAttributes(),
            $walmartSellingFormatTemplate->getMustShipAloneAttributes(),
            $walmartSellingFormatTemplate->getShipsInOriginalPackagingModeAttributes(),
            $walmartSellingFormatTemplate->getAttributesAttributes()
        );

        foreach ($walmartSellingFormatTemplate->getShippingOverrides(true) as $shippingOverride) {
            /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride $shippingOverride */

            if ($shippingOverride->isCostModeCustomAttribute()) {
                $trackingAttributes[] = $shippingOverride->getCostAttribute();
            }
        }

        if ($this->getWalmartListingProduct()->isExistCategoryTemplate()) {
            $categoryTemplate = $this->getWalmartListingProduct()->getCategoryTemplate();
            foreach ($categoryTemplate->getSpecifics(true) as $specific) {
                /** @var \Ess\M2ePro\Model\Walmart\Template\Category\Specific $specific */

                if ($specific->isModeCustomAttribute()) {
                    $trackingAttributes[] = $specific->getCustomAttribute();
                }
            }
        }

        $walmartDescriptionTemplate = $this->getWalmartListingProduct()->getWalmartDescriptionTemplate();
        $trackingAttributes = array_merge(
            $trackingAttributes,
            $walmartDescriptionTemplate->getTitleAttributes(),
            $walmartDescriptionTemplate->getBrandAttributes(),
            $walmartDescriptionTemplate->getCountPerPackAttributes(),
            $walmartDescriptionTemplate->getMultipackQuantityAttributes(),
            $walmartDescriptionTemplate->getDescriptionAttributes(),
            $walmartDescriptionTemplate->getKeyFeaturesAttributes(),
            $walmartDescriptionTemplate->getOtherFeaturesAttributes(),
            $walmartDescriptionTemplate->getAttributesAttributes(),
            $walmartDescriptionTemplate->getManufacturerAttributes(),
            $walmartDescriptionTemplate->getManufacturerPartNumberAttributes(),
            $walmartDescriptionTemplate->getMsrpRrpAttributes(),
            $walmartDescriptionTemplate->getKeywordsAttributes(),
            $walmartDescriptionTemplate->getImageMainAttributes(),
            $walmartDescriptionTemplate->getGalleryImagesAttributes(),
            $walmartDescriptionTemplate->getImageVariationDifferenceAttributes()
        );

        return array_unique($trackingAttributes);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    protected function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    //########################################
}
