<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual
 */
abstract class Individual extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $magentoVariationsSets = null;
    private $magentoVariationsCombinations = null;

    private $magentoVariationsTree = null;

    private $listingProduct = null;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        if ($this->listingProduct === null) {
            $this->listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Listing\Product',
                $this->getListingProductId()
            );
            $this->listingProduct->getMagentoProduct()->enableCache();
        }
        return $this->listingProduct;
    }

    public function getMagentoVariationsSets()
    {
        if ($this->magentoVariationsSets === null) {
            $temp = $this->getListingProduct()
                ->getMagentoProduct()
                ->getVariationInstance()
                ->getVariationsTypeStandard();
            $this->magentoVariationsSets = $temp['set'];
        }

        return $this->magentoVariationsSets;
    }

    public function getMagentoVariationsCombinations()
    {
        if ($this->magentoVariationsCombinations === null) {
            $temp = $this->getListingProduct()
                ->getMagentoProduct()
                ->getVariationInstance()
                ->getVariationsTypeStandard();
            $this->magentoVariationsCombinations = $temp['variations'];
        }

        return $this->magentoVariationsCombinations;
    }

    //########################################

    public function getMagentoVariationsTree()
    {
        if ($this->magentoVariationsTree === null) {
            $firstAttribute = $this->getMagentoVariationsSets();
            $firstAttribute = key($firstAttribute);

            $this->magentoVariationsTree = $this->prepareVariations(
                $firstAttribute,
                $this->getMagentoVariationsCombinations()
            );
        }

        return $this->magentoVariationsTree;
    }

    // ---------------------------------------

    private function prepareVariations($currentAttribute, $magentoVariations, $filters = [])
    {
        $return = false;

        $magentoVariationsSets = $this->getMagentoVariationsSets();

        $temp = array_flip(array_keys($magentoVariationsSets));

        if (!isset($temp[$currentAttribute])) {
            return false;
        }

        $lastAttributePosition = count($magentoVariationsSets) - 1;
        $currentAttributePosition = $temp[$currentAttribute];

        if ($currentAttributePosition != $lastAttributePosition) {
            $temp = array_keys($magentoVariationsSets);
            $nextAttribute = $temp[$currentAttributePosition + 1];

            foreach ($magentoVariationsSets[$currentAttribute] as $value) {
                $filters[$currentAttribute] = $value;

                $result = $this->prepareVariations(
                    $nextAttribute,
                    $magentoVariations,
                    $filters
                );

                if (!$result) {
                    continue;
                }

                $return[$currentAttribute][$value] = $result;
            }

            return $return;
        }

        $return = false;
        foreach ($magentoVariations as $key => $magentoVariation) {
            foreach ($magentoVariation as $option) {
                $value = $option['option'];
                $attribute = $option['attribute'];

                if ($attribute == $currentAttribute) {
                    if (count($magentoVariationsSets) != 1) {
                        continue;
                    }

                    $values = array_flip($magentoVariationsSets[$currentAttribute]);
                    $return = [$currentAttribute => $values];

                    foreach ($return[$currentAttribute] as &$value) {
                        $value = true;
                    }

                    return $return;
                }

                if ($value != $filters[$attribute]) {
                    unset($magentoVariations[$key]);
                    continue;
                }

                foreach ($magentoVariation as $tempOption) {
                    if ($tempOption['attribute'] == $currentAttribute) {
                        $value = $tempOption['option'];
                        $return[$currentAttribute][$value] = true;
                    }
                }
            }
        }

        if (count($magentoVariations) < 1) {
            return false;
        }

        return $return;
    }

    //########################################
}
