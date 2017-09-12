<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

class Updater extends \Ess\M2ePro\Model\Listing\Product\Variation\Updater
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     */
    public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!$listingProduct->getMagentoProduct()->isProductWithVariations()) {
            return;
        }

        $rawMagentoVariations = $listingProduct->getMagentoProduct()
                                               ->getVariationInstance()
                                               ->getVariationsTypeStandard();

        if (empty($rawMagentoVariations['set']) || !is_array($rawMagentoVariations['set']) ||
            empty($rawMagentoVariations['variations']) || !is_array($rawMagentoVariations['variations'])) {

            $rawMagentoVariations = array(
                'set'        => array(),
                'variations' => array()
            );
        }

        $rawMagentoVariations = $this->getHelper('Component\Ebay')
                                            ->reduceOptionsForVariations($rawMagentoVariations);

        $magentoVariations = $this->prepareMagentoVariations($rawMagentoVariations);

        if (!$listingProduct->getMagentoProduct()->isSimpleType() &&
            !$listingProduct->getMagentoProduct()->isDownloadableType()) {
            $this->inspectAndFixProductOptionsIds($listingProduct, $magentoVariations);
        }

        $currentVariations = $this->prepareCurrentVariations($listingProduct->getVariations(true));

        $addedVariations = $this->getAddedVariations($magentoVariations,$currentVariations);
        $deletedVariations = $this->getDeletedVariations($magentoVariations,$currentVariations);

        $this->addNewVariations($listingProduct,$addedVariations);
        $this->markAsDeletedVariations($deletedVariations);

        $this->saveVariationsData($listingProduct,$rawMagentoVariations);
    }

    //########################################

    protected function saveVariationsData(\Ess\M2ePro\Model\Listing\Product $listingProduct, $variationsData)
    {
        $additionalData = $listingProduct->getData('additional_data');
        $additionalData = is_null($additionalData) ? array()
                                                   : (array)$this->getHelper('Data')->jsonDecode($additionalData);

        if (isset($variationsData['set'])) {
            $additionalData['variations_sets'] = $variationsData['set'];
        }

        if (isset($variationsData['additional']['attributes'])) {
            $additionalData['configurable_attributes'] = $variationsData['additional']['attributes'];
        }

        $listingProduct->setData(
            'additional_data',
            $this->getHelper('Data')->jsonEncode($additionalData)
        )->save();
    }

    //########################################

    private function inspectAndFixProductOptionsIds(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                    $magentoVariations)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\Variation[] $listingProductVariations */
        $listingProductVariations = $listingProduct->getVariations(true);

        if (empty($listingProductVariations)) {
            return;
        }

        foreach ($listingProductVariations as $listingProductVariation) {

            $listingProductVariationOptions = $listingProductVariation->getOptions();

            foreach ($magentoVariations as $magentoVariation) {

                $magentoVariationOptions = $magentoVariation['options'];

                if (!$this->isEqualVariations($magentoVariationOptions, $listingProductVariationOptions)) {
                    continue;
                }

                foreach ($listingProductVariationOptions as $listingProductVariationOption) {
                    foreach ($magentoVariationOptions as $magentoVariationOption) {

                        if ($listingProductVariationOption['attribute'] != $magentoVariationOption['attribute'] ||
                            $listingProductVariationOption['option'] != $magentoVariationOption['option']) {
                            continue;
                        }

                        if ($listingProductVariationOption['product_id'] == $magentoVariationOption['product_id']) {
                            continue;
                        }

                        $listingProductVariationOption['product_id'] = $magentoVariationOption['product_id'];

                        $this->ebayFactory->getObject('Listing\Product\Variation\Option')
                            ->setData($listingProductVariationOption)->save();
                    }
                }
            }
        }
    }

    private function getAddedVariations($magentoVariations, $currentVariations)
    {
        $result = array();

        foreach ($magentoVariations as $mVariation) {

            $isExistVariation = false;
            $cVariationExist = NULL;

            foreach ($currentVariations as $cVariation) {
                if ($this->isEqualVariations($mVariation['options'],$cVariation['options'])) {
                    $isExistVariation = true;
                    $cVariationExist = $cVariation;
                    break;
                }
            }

            if (!$isExistVariation) {
                $result[] = $mVariation;
            } else {
                if ((bool)$cVariationExist['variation']['delete']) {
                    $result[] = $cVariationExist;
                }
            }
        }

        return $result;
    }

    private function getDeletedVariations($magentoVariations, $currentVariations)
    {
        $result = array();
        $foundedVariations = array();

        foreach ($currentVariations as $cVariation) {

            if ((bool)$cVariation['variation']['delete']) {
                continue;
            }

            $isExistVariation = false;
            $variationHash = $this->getVariationHash($cVariation);

            foreach ($magentoVariations as $mVariation) {
                if ($this->isEqualVariations($mVariation['options'],$cVariation['options'])) {

                    // so it is a duplicated variation. have to be deleted
                    if (in_array($variationHash, $foundedVariations)) {
                        $result[] = $cVariation;
                        continue 2;
                    }

                    $foundedVariations[] = $variationHash;
                    $isExistVariation = true;
                    break;
                }
            }

            if (!$isExistVariation) {
                $result[] = $cVariation;
            }
        }

        return $result;
    }

    // ---------------------------------------

    private function addNewVariations(\Ess\M2ePro\Model\Listing\Product $listingProduct, $addedVariations)
    {
        foreach ($addedVariations as $aVariation) {

            if (isset($aVariation['variation']['id'])) {

                $status = $aVariation['variation']['status'];

                $dataForUpdate = array(
                    'add'    => $status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED ? 1 : 0,
                    'delete' => 0
                );

                $listingProductVariation = $this->ebayFactory->getObjectLoaded(
                    'Listing\Product\Variation', $aVariation['variation']['id']
                );
                $listingProductVariation->getChildObject()->addData($dataForUpdate);
                $listingProductVariation->save();

                continue;
            }

            $dataForAdd = array(
                'listing_product_id' => $listingProduct->getId(),
                'add'                => 1,
                'delete'             => 0,
                'status'             => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
            );

            $newVariationId = $this->ebayFactory->getObject('Listing\Product\Variation')
                ->addData($dataForAdd)->save()->getId();

            foreach ($aVariation['options'] as $aOption) {

                $dataForAdd = array(
                    'listing_product_variation_id' => $newVariationId,
                    'product_id'                   => $aOption['product_id'],
                    'product_type'                 => $aOption['product_type'],
                    'attribute'                    => $aOption['attribute'],
                    'option'                       => $aOption['option']
                );

                $this->ebayFactory->getObject('Listing\Product\Variation\Option')->addData($dataForAdd)->save();
            }
        }
    }

    private function markAsDeletedVariations($deletedVariations)
    {
        foreach ($deletedVariations as $dVariation) {

            if ($dVariation['variation']['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {

                $this->ebayFactory->getObjectLoaded(
                    'Listing\Product\Variation',
                    $dVariation['variation']['id']
                )->delete();

            } else {

                $dataForUpdate = array(
                    'add'    => 0,
                    'delete' => 1
                );

                $listingProductVariation = $this->ebayFactory->getObjectLoaded(
                    'Listing\Product\Variation', $dVariation['variation']['id']
                );
                $listingProductVariation->getChildObject()->addData($dataForUpdate);
                $listingProductVariation->save();
            }
        }
    }

    //########################################

    private function prepareMagentoVariations($variations)
    {
        $result = array();

        if (isset($variations['variations'])) {
            $variations = $variations['variations'];
        }

        foreach ($variations as $variation) {
            $result[] = array(
                'variation' => array(),
                'options' => $variation
            );
        }

        return $result;
    }

    private function prepareCurrentVariations($variations)
    {
        $result = array();

        foreach ($variations as $variation) {

            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

            $tmpVariationData = $variation->getData();
            $tmpVariationData = array_merge($tmpVariationData, $variation->getChildObject()->getData());

            $temp = array(
                'variation' => $tmpVariationData,
                'options' => array()
            );

            foreach ($variation->getOptions(false) as $option) {
                $temp['options'][] = $option;
            }

            $result[] = $temp;
        }

        return $result;
    }

    // ---------------------------------------

    private function isEqualVariations($magentoVariation, $currentVariation)
    {
        if (count($magentoVariation) != count($currentVariation)) {
            return false;
        }

        foreach ($magentoVariation as $mOption) {

            $haveOption = false;

            foreach ($currentVariation as $cOption) {
                if ($mOption['attribute'] == $cOption['attribute'] &&
                    $mOption['option'] == $cOption['option']) {
                    $haveOption = true;
                    break;
                }
            }

            if (!$haveOption) {
                return false;
            }
        }

        return true;
    }

    private function getVariationHash($variation)
    {
        $hash = array();

        foreach ($variation['options'] as $option) {
            $hash[] = $option['attribute'] .'-'. $option['option'];
        }

        return implode('##', $hash);
    }

    //########################################
}