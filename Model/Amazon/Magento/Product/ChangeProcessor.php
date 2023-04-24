<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product;

class ChangeProcessor extends \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel
{
    public const INSTRUCTION_TYPE_QTY_DATA_CHANGED = 'magento_product_qty_data_changed';
    public const INSTRUCTION_TYPE_DETAILS_DATA_CHANGED = 'magento_product_details_data_changed';
    public const INSTRUCTION_TYPE_REPRICING_DATA_CHANGED = 'magento_product_repricing_data_changed';

    public function getTrackingAttributes()
    {
        return array_unique(
            array_merge(
                $this->getQtyTrackingAttributes(),
                $this->getDetailsTrackingAttributes(),
                $this->getRepricingTrackingAttributes()
            )
        );
    }

    public function getInstructionsDataByAttributes(array $attributes)
    {
        if (empty($attributes)) {
            return [];
        }

        $data = [];

        if (array_intersect($attributes, $this->getQtyTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 40;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
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

        if (array_intersect($attributes, $this->getRepricingTrackingAttributes())) {
            $priority = 5;

            if ($this->getListingProduct()->isListed()) {
                $priority = 70;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_REPRICING_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }

    public function getQtyTrackingAttributes()
    {
        $amazonListing = $this->getAmazonListingProduct()->getAmazonListing();

        $trackingAttributes = array_merge(
            $amazonListing->getHandlingTimeAttributes(),
            $amazonListing->getRestockDateAttributes()
        );

        return array_unique($trackingAttributes);
    }

    public function getDetailsTrackingAttributes()
    {
        $trackingAttributes = [];

        $amazonListing = $this->getAmazonListingProduct()->getAmazonListing();

        $trackingAttributes = array_merge(
            $trackingAttributes,
            $amazonListing->getConditionNoteAttributes(),
            $amazonListing->getGiftWrapAttributes(),
            $amazonListing->getGiftMessageAttributes()
        );

        if ($this->getAmazonListingProduct()->isExistsProductTypeTemplate()) {
            $productType = $this->getAmazonListingProduct()->getProductTypeTemplate();
            $trackingAttributes = array_merge(
                $trackingAttributes,
                $productType->getCustomAttributesName()
            );
        }

        if ($this->getAmazonListingProduct()->isExistProductTaxCodeTemplate()) {
            $productTaxCodeTemplate = $this->getAmazonListingProduct()->getProductTaxCodeTemplate();

            $trackingAttributes = array_merge(
                $trackingAttributes,
                $productTaxCodeTemplate->getProductTaxCodeAttributes()
            );
        }

        return array_unique($trackingAttributes);
    }

    public function getRepricingTrackingAttributes()
    {
        $trackingAttributes = [];

        if (!$this->getAmazonListingProduct()->isRepricingUsed()) {
            return $trackingAttributes;
        }

        $accountRepricing = $this->getAmazonListingProduct()->getRepricing()->getAccountRepricing();

        $trackingAttributes = array_merge(
            $trackingAttributes,
            $accountRepricing->getDisableAttributes(),
            $accountRepricing->getRegularPriceAttributes(),
            $accountRepricing->getMinPriceAttributes(),
            $accountRepricing->getMaxPriceAttributes()
        );

        return array_unique($trackingAttributes);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    protected function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }
}
