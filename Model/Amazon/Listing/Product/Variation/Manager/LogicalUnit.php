<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager;

class LogicalUnit extends \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\AbstractModel
{
    //########################################

    /**
     * @return bool
     */
    public function isActualProductAttributes()
    {
        $productAttributes = array_map('strtolower', (array)$this->getProductAttributes());
        $magentoAttributes = array_map('strtolower', (array)$this->getMagentoAttributes());

        sort($productAttributes);
        sort($magentoAttributes);

        return $productAttributes == $magentoAttributes;
    }

    //########################################

    public function getProductAttributes()
    {
        return $this->getListingProduct()->getSetting('additional_data', 'variation_product_attributes', array());
    }

    public function resetProductAttributes($save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_product_attributes', $this->getMagentoAttributes()
        );

        $save && $this->getListingProduct()->save();
    }

    //########################################

    public function clearTypeData()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();
        unset($additionalData['variation_product_attributes']);
        $this->getListingProduct()->setSettings('additional_data', $additionalData);

        $this->getListingProduct()->save();
    }

    //########################################

    protected function getMagentoAttributes()
    {
        $magentoVariations = $this->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();
        return array_keys($magentoVariations['set']);
    }

    //########################################
}