<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Magento\Product;

class ChangeProcessor extends \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel
{
    public const INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED = 'magento_product_promotions_data_changed';
    public const INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED = 'magento_product_lag_time_data_changed';
    public const INSTRUCTION_TYPE_DETAILS_DATA_CHANGED = 'magento_product_details_data_changed';
    public const INSTRUCTION_TYPE_REPRICER_DATA_CHANGED = 'magento_product_repricer_data_changed';

    //########################################

    public function getTrackingAttributes(): array
    {
        return array_unique(
            array_merge(
                $this->getLagTimeTrackingAttributes(),
                $this->getPromotionsTrackingAttributes(),
                $this->getDetailsTrackingAttributes(),
                $this->getRepricerTrackingAttributes(),
            )
        );
    }

    public function getInstructionsDataByAttributes(array $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        $data = [];

        if (array_intersect($attributes, $this->getRepricerTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 60;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_REPRICER_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if (array_intersect($attributes, $this->getLagTimeTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 40;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if (array_intersect($attributes, $this->getPromotionsTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 40;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if (array_intersect($attributes, $this->getDetailsTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }

    //########################################

    private function getLagTimeTrackingAttributes(): array
    {
        $walmartSellingFormatTemplate = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate();

        $trackingAttributes = array_merge(
            $walmartSellingFormatTemplate->getLagTimeAttributes()
        );

        return array_unique($trackingAttributes);
    }

    private function getPromotionsTrackingAttributes(): array
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

    private function getDetailsTrackingAttributes(): array
    {
        $trackingAttributes = [];

        $walmartSellingFormatTemplate = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate();

        $trackingAttributes = array_merge(
            $trackingAttributes,
            $walmartSellingFormatTemplate->getSaleTimeStartDateAttributes(),
            $walmartSellingFormatTemplate->getSaleTimeEndDateAttributes(),
            $walmartSellingFormatTemplate->getItemWeightAttributes(),
            $walmartSellingFormatTemplate->getMustShipAloneAttributes(),
            $walmartSellingFormatTemplate->getShipsInOriginalPackagingModeAttributes(),
        );

        if ($this->getWalmartListingProduct()->isExistsProductType()) {
            $productType = $this->getWalmartListingProduct()->getProductType();
            foreach ($productType->getAttributesSettings() as $attributesSetting) {
                foreach ($attributesSetting->getValues() as $attributeValue) {
                    if ($attributeValue->isProductAttributeCode()) {
                        $trackingAttributes[] = $attributeValue->getValue();
                    }
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
            $walmartDescriptionTemplate->getManufacturerAttributes(),
            $walmartDescriptionTemplate->getManufacturerPartNumberAttributes(),
            $walmartDescriptionTemplate->getMsrpRrpAttributes(),
            $walmartDescriptionTemplate->getImageMainAttributes(),
            $walmartDescriptionTemplate->getGalleryImagesAttributes(),
            $walmartDescriptionTemplate->getImageVariationDifferenceAttributes()
        );

        return array_unique($trackingAttributes);
    }

    private function getRepricerTrackingAttributes(): array
    {
        $repricerTemplate = $this->getWalmartListingProduct()->getRepricerTemplate();
        if ($repricerTemplate === null) {
            return [];
        }

        return array_unique($repricerTemplate->getRepricerAttributes());
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
