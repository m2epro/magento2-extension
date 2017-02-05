<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku;

class General extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    const SKU_MAX_LENGTH = 40;

    //########################################

    /**
     * @return bool
     */
    public function validate()
    {
        $sku = $this->getSku();

        if (empty($sku)) {

            // M2ePro\TRANSLATIONS
            // SKU is not provided. Please, check Listing Settings.
            $this->addMessage('SKU is not provided. Please, check Listing Settings.');

            return false;
        }

        if (strlen($sku) > self::SKU_MAX_LENGTH) {

            // M2ePro\TRANSLATIONS
            // The length of SKU must be less than 40 characters.
            $this->addMessage('The length of SKU must be less than 40 characters.');

            return false;
        }

        $this->setData('sku', $sku);

        return true;
    }

    //########################################

    private function getSku()
    {
        if (isset($this->getData()['sku'])) {
            return $this->getData('sku');
        }

        $sku = $this->getAmazonListingProduct()->getSku();
        if (!empty($sku)) {
            return $sku;
        }

        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getListingProduct()->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception\Logic('There are no variations for a variation product.',
                    array(
                        'listing_product_id' => $this->getListingProduct()->getId()
                    ));
            }
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);
            return $variation->getChildObject()->getSku();
        }

        return $this->getAmazonListingProduct()->getListingSource()->getSku();
    }

    //########################################
}