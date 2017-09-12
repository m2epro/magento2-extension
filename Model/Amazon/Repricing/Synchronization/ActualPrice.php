<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Synchronization;

class ActualPrice extends AbstractModel
{
    //########################################

    public function run($skus = NULL)
    {
        $existedSkus = array_unique(array_merge(
            $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')->getResource()->getAllSkus(
                $this->getAccount(), false
            ),
            $this->activeRecordFactory->getObject('Amazon\Listing\Other')->getResource()->getAllRepricingSkus(
                $this->getAccount(), false
            )
        ));

        if (is_null($skus)) {
            $requestSkus = $existedSkus;
        } else {
            $requestSkus = array_intersect($skus, $existedSkus);
        }

        if (empty($requestSkus)) {
            return false;
        }

        $response = $this->sendRequest([
            'skus_list' => $requestSkus,
        ]);

        if ($response === false || empty($response['status'])) {
            return false;
        }

        $offersProductPrices = [];
        foreach ($response['offers'] as $offerData) {
            $productPrice = $offerData['product_price'];
            if (is_null($productPrice)) {
                continue;
            }

            $offersProductPrices[strtolower($offerData['sku'])] = $productPrice;
        }

        if (empty($offersProductPrices)) {
            return false;
        }

        $this->updateListingsProductsPrices($offersProductPrices);
        $this->updateListingsOthersPrices($offersProductPrices);

        return true;
    }

    //########################################

    protected function getMode()
    {
        return self::MODE_ACTUAL_PRICE;
    }

    //########################################

    private function updateListingsProductsPrices(array $offersProductPrices)
    {
        $keys = array_map(function($el){ return (string)$el; }, array_keys($offersProductPrices));

        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);

        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'l.id = main_table.listing_id',
            []
        );
        $listingProductCollection->getSelect()->joinInner(
            [
                'alpr' => $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
                    ->getResource()->getMainTable()
            ],
            'alpr.listing_product_id=main_table.id',
            []
        );
        $listingProductCollection->addFieldToFilter('l.account_id', $this->getAccount()->getId());
        $listingProductCollection->addFieldToFilter('sku', ['in' => $keys]);

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            [
                'main_table.product_id',
                'second_table.listing_product_id',
                'second_table.sku',
                'second_table.online_regular_price',
            ]
        );

        $listingsProductsData = $listingProductCollection->getData();

        foreach ($listingsProductsData as $listingProductData) {
            $listingProductId = (int)$listingProductData['listing_product_id'];

            $offerProductPrice = $offersProductPrices[strtolower($listingProductData['sku'])];

            if (!is_null($offerProductPrice) &&
                $listingProductData['online_regular_price'] != $offerProductPrice
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product'),
                    ['online_regular_price' => $offerProductPrice],
                    ['listing_product_id = ?' => $listingProductId]
                );
            }
        }
    }

    private function updateListingsOthersPrices(array $offersProductPrices)
    {
        $keys = array_map(function($el){ return (string)$el; }, array_keys($offersProductPrices));

        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $this->getAccount()->getId());
        $listingOtherCollection->addFieldToFilter('sku', ['in' => $keys]);
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            [
                'second_table.listing_other_id',
                'second_table.sku',
                'second_table.online_price',
            ]
        );

        $listingsOthersData = $listingOtherCollection->getData();

        if (empty($listingsOthersData)) {
            return;
        }

        foreach ($listingsOthersData as $listingOtherData) {
            $listingOtherId = (int)$listingOtherData['listing_other_id'];

            $offerProductPrice = $offersProductPrices[strtolower($listingOtherData['sku'])];

            if (!is_null($offerProductPrice) &&
                $offerProductPrice != $listingOtherData['online_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_other'),
                    [
                        'online_price' => $offerProductPrice,
                    ],
                    ['listing_other_id = ?' => $listingOtherId]
                );
            }
        }
    }

    //########################################
}