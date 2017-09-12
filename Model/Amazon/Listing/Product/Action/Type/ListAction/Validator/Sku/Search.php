<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku;

class Search extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Validator
{
    private $skusInProcessing = NULL;

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function validate()
    {
        $sku = $this->getSku();

        $generateSkuMode = $this->getAmazonListingProduct()->getAmazonListing()->isGenerateSkuModeYes();

        if (!$this->isExistInM2ePro($sku, !$generateSkuMode)) {
            return true;
        }

        if (!$generateSkuMode) {
            return false;
        }

        $unifiedSku = $this->getUnifiedSku($sku);
        if ($this->checkSkuRequirements($unifiedSku)) {
            $this->setData('sku', $unifiedSku);
            return true;
        }

        if ($this->getVariationManager()->isIndividualType() || $this->getVariationManager()->isRelationChildType()) {
            $baseSku = $this->getAmazonListing()->getSource($this->getMagentoProduct())->getSku();

            $unifiedBaseSku = $this->getUnifiedSku($baseSku);
            if ($this->checkSkuRequirements($unifiedBaseSku)) {
                $this->setData('sku', $unifiedBaseSku);
                return true;
            }
        }

        $unifiedSku = $this->getUnifiedSku();
        if ($this->checkSkuRequirements($unifiedSku)) {
            $this->setData('sku', $unifiedSku);
            return true;
        }

        $randomSku = $this->getRandomSku();
        if ($this->checkSkuRequirements($randomSku)) {
            $this->setData('sku', $randomSku);
            return true;
        }

        // M2ePro\TRANSLATIONS
        // SKU generating is not successful.
        $this->addMessage('SKU generating is not successful.');

        return false;
    }

    //########################################

    private function getSku()
    {
        if (empty($this->getData('sku'))) {
            throw new \Ess\M2ePro\Model\Exception('SKU is not defined.');
        }

        return $this->getData('sku');
    }

    private function getUnifiedSku($prefix = 'SKU')
    {
        return $prefix.'_'.$this->getListingProduct()->getProductId().'_'.$this->getListingProduct()->getId();
    }

    private function getRandomSku()
    {
        $hash = sha1(rand(0,10000).microtime(1));
        return $this->getUnifiedSku().'_'.substr($hash, 0, 10);
    }

    //########################################

    private function checkSkuRequirements($sku)
    {
        if ($sku>\Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Validator\Sku\General::SKU_MAX_LENGTH){
            return false;
        }

        if ($this->isExistInM2ePro($sku, false)) {
            return false;
        }

        return true;
    }

    //########################################

    private function isExistInM2ePro($sku, $addMessages = false)
    {
        if ($this->isAlreadyInProcessing($sku)) {
// M2ePro\TRANSLATIONS
// Another Product with the same SKU is being Listed simultaneously with this one. Please change the SKU or enable the Option Generate Merchant SKU.
            $addMessages && $this->addMessage('Another Product with the same SKU is being Listed simultaneously
                                with this one. Please change the SKU or enable the Option Generate Merchant SKU.');
            return true;
        }

        if ($this->isExistInM2eProListings($sku)) {
// M2ePro\TRANSLATIONS
// Product with the same SKU is found in other M2E Pro Listing that is created from the same Merchant ID for the same Marketplace.
            $addMessages && $this->addMessage(
                'Product with the same SKU is found in other M2E Pro Listing that is created
                 from the same Merchant ID for the same Marketplace.'
            );
            return true;
        }

        if ($this->isExistInOtherListings($sku)) {
// M2ePro\TRANSLATIONS
// Product with the same SKU is found in M2E Pro 3rd Party Listing. Please change the SKU or enable the Option Generate Merchant SKU.
            $addMessages && $this->addMessage('Product with the same SKU is found in M2E Pro 3rd Party Listing.
                                            Please change the SKU or enable the Option Generate Merchant SKU.');
            return true;
        }

        return false;
    }

    // ---------------------------------------

    private function isAlreadyInProcessing($sku)
    {
        return in_array($sku, $this->getSkusInProcessing());
    }

    private function isExistInM2eProListings($sku)
    {
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->join(
            array('l'=>$listingTable),
            '`main_table`.`listing_id` = `l`.`id`',
            array()
        );

        $collection->addFieldToFilter('sku',$sku);
        $collection->addFieldToFilter('account_id',$this->getListingProduct()->getAccount()->getId());

        return $collection->getSize() > 0;
    }

    private function isExistInOtherListings($sku)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $collection */
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

        $collection->addFieldToFilter('sku',$sku);
        $collection->addFieldToFilter('account_id',$this->getListingProduct()->getAccount()->getId());

        return $collection->getSize() > 0;
    }

    //########################################

    private function getSkusInProcessing()
    {
        if (!is_null($this->skusInProcessing)) {
            return $this->skusInProcessing;
        }

        $processingActionListSkuCollection = $this->activeRecordFactory
                                                  ->getObject('Amazon\Processing\Action\ListAction\Sku')
                                                  ->getCollection();
        $processingActionListSkuCollection->addFieldToFilter('account_id', $this->getListing()->getAccountId());

        return $this->skusInProcessing = $processingActionListSkuCollection->getColumnValues('sku');
    }

    //########################################
}