<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

use Ess\M2ePro\Helper\Component\Amazon\Repricing;

class Synchronization extends AbstractModel
{
    //########################################

    public function runFull()
    {
        $response = $this->sendRequest();

        if ($response === false || empty($response['status'])) {
            return false;
        }

        if (!empty($response['email'])) {
            $this->getAmazonAccountRepricing()->setData('email', $response['email']);
        }

        $this->processResultOffers($response['offers']);

        $this->getAmazonAccountRepricing()->setData('total_products', count($response['offers']));
        $this->getAmazonAccountRepricing()->save();

        return true;
    }

    public function runBySkus(array $skus)
    {
        $response = $this->sendRequest(array(
            'skus_list' => $skus,
        ));

        if ($response === false || empty($response['status'])) {
            return false;
        }

        $this->processResultOffers($response['offers'], $skus);

        return true;
    }

    //########################################

    private function sendRequest(array $filters = array())
    {
        $requestData = array(
            'account_token' => $this->getAmazonAccountRepricing()->getToken(),
        );

        if (!empty($filters)) {
            foreach ($filters as $name => $value) {
                $filters[$name] = json_encode($value);
            }

            $requestData['filters'] = $filters;
        }

        try {
            $result = $this->getHelper('Component\Amazon\Repricing')->sendRequest(
                Repricing::COMMAND_SYNCHRONIZE,
                $requestData
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        return json_decode($result['response'], true);
    }

    //########################################

    private function processResultOffers(array $resultOffersData, array $requestedSkus = array())
    {
        $existedSkus = array_unique(array_merge(
            $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')->getResource()->getAllSkus(
                $this->getAccount()
            ),
            $this->activeRecordFactory->getObject('Amazon\Listing\Other')->getResource()->getAllRepricingSkus(
                $this->getAccount()
            )
        ));

        if (!empty($requestedSkus)) {
            $existedSkus = array_intersect($requestedSkus, $existedSkus);
        }

        $existedSkus = array_map('strtolower', $existedSkus);

        foreach ($resultOffersData as $key => $offerData) {
            $resultOffersData[strtolower($offerData['sku'])] = $offerData;
            unset($resultOffersData[$key]);
        }

        $this->processNewOffers($resultOffersData, $existedSkus);
        $this->processRemovedOffers($resultOffersData, $existedSkus);
        $this->processUpdatedOffers($resultOffersData, $existedSkus);
    }

    //----------------------------------------

    private function processNewOffers(array $resultOffersData, array $existedSkus)
    {
        $newOffersSkus = array_diff(array_keys($resultOffersData), $existedSkus);
        if (empty($newOffersSkus)) {
            return;
        }

        $newOffersData = array();
        foreach ($newOffersSkus as $newOfferSku) {
            $newOffersData[$newOfferSku] = $resultOffersData[$newOfferSku];
        }

        $this->addListingsProductsRepricing($newOffersData);
        $this->addListingOthersRepricing($newOffersData);
    }

    private function processRemovedOffers(array $resultOffersData, array $existedSkus)
    {
        $removedOffersSkus = array_diff($existedSkus, array_keys($resultOffersData));
        if (empty($removedOffersSkus)) {
            return;
        }

        $skusPacks = array_chunk($removedOffersSkus, 1000);

        foreach ($skusPacks as $skuPack) {
            $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')->getResource()->remove(
                $this->getAccount(), $skuPack
            );
            $this->activeRecordFactory->getObject('Amazon\Listing\Other')->getResource()->removeRepricing(
                $this->getAccount(), $skuPack
            );
        }
    }

    private function processUpdatedOffers(array $resultOffersData, array $existedSkus)
    {
        $updatedOffersSkus = array_intersect($existedSkus, array_keys($resultOffersData));
        if (empty($updatedOffersSkus)) {
            return;
        }

        $updatedOffersData = array();
        foreach ($updatedOffersSkus as $updatedOfferSku) {
            $updatedOffersData[$updatedOfferSku] = $resultOffersData[$updatedOfferSku];
        }

        $this->updateListingsProductsRepricing($updatedOffersData);
        $this->updateListingsOthersRepricing($updatedOffersData);
    }

    //########################################

    private function addListingsProductsRepricing(array $newOffersData)
    {
        $listingProductCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
            'l.id = main_table.listing_id',
            array()
        );
        $listingProductCollection->addFieldToFilter('l.account_id', $this->getAccount()->getId());
        $listingProductCollection->addFieldToFilter('sku', array('in' => array_keys($newOffersData)));

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            array(
                'second_table.listing_product_id',
                'second_table.sku',
                'second_table.online_price',
            )
        );

        $listingsProductsData = $listingProductCollection->getData();

        if (empty($listingsProductsData)) {
            return;
        }

        $insertData = array();

        foreach ($listingsProductsData as $listingProductData) {
            $listingProductId = (int)$listingProductData['listing_product_id'];

            $offerData = $newOffersData[strtolower($listingProductData['sku'])];

            $insertData[$listingProductId] = array(
                'listing_product_id'   => $listingProductId,
                'online_regular_price' => $offerData['regular_product_price'],
                'online_min_price'     => $offerData['minimal_product_price'],
                'online_max_price'     => $offerData['maximal_product_price'],
                'is_online_disabled'   => $offerData['is_calculation_disabled'],
                'update_date'          => $this->getHelper('Data')->getCurrentGmtDate(),
                'create_date'          => $this->getHelper('Data')->getCurrentGmtDate(),
            );

            if (!is_null($offerData['product_price']) &&
                $offerData['product_price'] != $listingProductData['online_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product'),
                    array('online_price' => $offerData['product_price']),
                    array('listing_product_id = ?' => $listingProductId)
                );
            }
        }

        foreach (array_chunk($insertData, 1000, true) as $insertDataPack) {
            $this->resourceConnection->getConnection()->insertMultiple(
                $this->resourceConnection->getTableName('m2epro_amazon_listing_product_repricing'),
                $insertDataPack
            );

            $this->resourceConnection->getConnection()->update(
                $this->resourceConnection->getTableName('m2epro_amazon_listing_product'),
                array(
                    'online_sale_price'            => 0,
                    'online_sale_price_start_date' => NULL,
                    'online_sale_price_end_date'   => NULL,
                ),
                array('listing_product_id IN (?)' => array_keys($insertDataPack))
            );
        }
    }

    private function addListingOthersRepricing(array $newOffersData)
    {
        $listingOtherCollection = $this->activeRecordFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $this->getAccount()->getId());
        $listingOtherCollection->addFieldToFilter('sku', array('in' => array_keys($newOffersData)));

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array(
                'second_table.listing_other_id',
                'second_table.sku',
                'second_table.online_price'
            )
        );

        $listingsOthersData = $listingOtherCollection->getData();
        if (empty($listingsOthersData)) {
            return;
        }

        $disabledListingOthersIds = array();
        $enabledListingOthersIds  = array();

        foreach ($listingsOthersData as $listingOtherData) {
            $listingOtherId = (int)$listingOtherData['listing_other_id'];

            $offerData = $newOffersData[strtolower($listingOtherData['sku'])];

            if (!is_null($offerData['product_price']) &&
                $offerData['product_price'] != $listingOtherData['online_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getConnection()->getTableName('m2epro_amazon_listing_other'),
                    array(
                        'online_price'          => $offerData['product_price'],
                        'is_repricing'          => 1,
                        'is_repricing_disabled' => $offerData['is_calculation_disabled'],
                    ),
                    array('listing_other_id = ?' => $listingOtherId)
                );

                continue;
            }

            if ($offerData['is_calculation_disabled']) {
                $disabledListingOthersIds[] = $listingOtherId;
            } else {
                $enabledListingOthersIds[] = $listingOtherId;
            }
        }

        if (!empty($disabledListingOthersIds)) {

            $disabledListingOthersIdsPacks = array_chunk(array_unique($disabledListingOthersIds), 1000);

            foreach ($disabledListingOthersIdsPacks as $disabledListingOthersIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_other'),
                    array(
                        'is_repricing'          => 1,
                        'is_repricing_disabled' => 1,
                    ),
                    array('listing_other_id IN (?)' => $disabledListingOthersIdsPack)
                );
            }
        }

        if (!empty($enabledListingOthersIds)) {

            $enabledListingOthersIdsPacks = array_chunk(array_unique($enabledListingOthersIds), 1000);

            foreach ($enabledListingOthersIdsPacks as $enabledListingOthersIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getConnection()->getTableName('m2epro_amazon_listing_other'),
                    array(
                        'is_repricing'          => 1,
                        'is_repricing_disabled' => 0,
                    ),
                    array('listing_other_id IN (?)' => $enabledListingOthersIdsPack)
                );
            }
        }
    }

    //----------------------------------------

    private function updateListingsProductsRepricing(array $updatedOffersData)
    {
        $listingProductCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->resourceConnection->getTableName('m2epro_listing')),
            'l.id = main_table.listing_id',
            array()
        );
        $listingProductCollection->getSelect()->joinInner(
            array('alpr' => $this->resourceConnection->getTableName('m2epro_amazon_listing_product_repricing')),
            'alpr.listing_product_id=main_table.id',
            array()
        );
        $listingProductCollection->addFieldToFilter('l.account_id', $this->getAccount()->getId());
        $listingProductCollection->addFieldToFilter('sku', array('in' => array_keys($updatedOffersData)));

        $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductCollection->getSelect()->columns(
            array(
                'second_table.listing_product_id',
                'second_table.sku',
                'second_table.online_price',
                'alpr.is_online_disabled',
                'alpr.online_regular_price',
                'alpr.online_min_price',
                'alpr.online_max_price'
            )
        );

        $listingsProductsData = $listingProductCollection->getData();

        $disabledListingsProductsIds = array();
        $enabledListingsProductsIds  = array();

        foreach ($listingsProductsData as $listingProductData) {
            $listingProductId = (int)$listingProductData['listing_product_id'];

            $offerData = $updatedOffersData[strtolower($listingProductData['sku'])];

            if (!is_null($offerData['product_price']) &&
                $listingProductData['online_price'] != $offerData['product_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product'),
                    array('online_price' => $offerData['product_price']),
                    array('listing_product_id = ?' => $listingProductId)
                );
            }

            if ($listingProductData['online_regular_price'] != $offerData['regular_product_price'] ||
                $listingProductData['online_min_price'] != $offerData['minimal_product_price'] ||
                $listingProductData['online_max_price'] != $offerData['maximal_product_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product_repricing'),
                    array(
                        'online_regular_price' => $offerData['regular_product_price'],
                        'online_min_price'     => $offerData['minimal_product_price'],
                        'online_max_price'     => $offerData['maximal_product_price'],
                        'is_online_disabled'   => $offerData['is_calculation_disabled'],
                        'update_date'          => $this->getHelper('Data')->getCurrentGmtDate(),
                    ),
                    array('listing_product_id = ?' => $listingProductId)
                );

                continue;
            }

            if ($listingProductData['is_online_disabled'] != $offerData['is_calculation_disabled']) {
                $offerData['is_calculation_disabled'] && $disabledListingsProductsIds[] = $listingProductId;
                !$offerData['is_calculation_disabled'] && $enabledListingsProductsIds[] = $listingProductId;
            }
        }

        if (!empty($disabledListingsProductsIds)) {

            $disabledListingsProductsIdsPacks = array_chunk(array_unique($disabledListingsProductsIds), 1000);

            foreach ($disabledListingsProductsIdsPacks as $disabledListingsProductsIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product_repricing'),
                    array(
                        'is_online_disabled' => 1,
                        'update_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                    ),
                    array('listing_product_id IN (?)' => $disabledListingsProductsIdsPack)
                );
            }
        }

        if (!empty($enabledListingsProductsIds)) {

            $enabledListingsProductsIdsPacks = array_chunk(array_unique($enabledListingsProductsIds), 1000);

            foreach ($enabledListingsProductsIdsPacks as $enabledListingsProductsIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_product_repricing'),
                    array(
                        'is_online_disabled' => 0,
                        'update_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                    ),
                    array('listing_product_id IN (?)' => $enabledListingsProductsIdsPack)
                );
            }
        }
    }

    private function updateListingsOthersRepricing(array $updatedOffersData)
    {
        $listingOtherCollection = $this->activeRecordFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $this->getAccount()->getId());
        $listingOtherCollection->addFieldToFilter('sku', array('in' => array_keys($updatedOffersData)));
        $listingOtherCollection->addFieldToFilter('is_repricing', 1);

        $listingOtherCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingOtherCollection->getSelect()->columns(
            array(
                'second_table.listing_other_id',
                'second_table.sku',
                'second_table.online_price',
                'second_table.is_repricing_disabled',
            )
        );

        $listingsOthersData = $listingOtherCollection->getData();

        if (empty($listingsOthersData)) {
            return;
        }

        $disabledListingOthersIds = array();
        $enabledListingOthersIds  = array();

        foreach ($listingsOthersData as $listingOtherData) {
            $listingOtherId = (int)$listingOtherData['listing_other_id'];

            $offerData = $updatedOffersData[strtolower($listingOtherData['sku'])];

            if (!is_null($offerData['product_price']) &&
                $offerData['product_price'] != $listingOtherData['online_price']
            ) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_other'),
                    array(
                        'online_price'          => $offerData['product_price'],
                        'is_repricing_disabled' => $offerData['is_calculation_disabled'],
                    ),
                    array('listing_other_id = ?' => $listingOtherId)
                );

                continue;
            }

            if ($listingOtherData['is_repricing_disabled'] != $offerData['is_calculation_disabled']) {
                $offerData['is_calculation_disabled'] && $disabledListingOthersIds[] = $listingOtherId;
                !$offerData['is_calculation_disabled'] && $enabledListingOthersIds[] = $listingOtherId;
            }
        }

        if (!empty($disabledListingOthersIds)) {

            $disabledListingOthersIdsPacks = array_chunk(array_unique($disabledListingOthersIds), 1000);

            foreach ($disabledListingOthersIdsPacks as $disabledListingOthersIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_other'),
                    array('is_repricing_disabled' => 1),
                    array('listing_other_id IN (?)' => $disabledListingOthersIdsPack)
                );
            }
        }

        if (!empty($enabledListingOthersIds)) {

            $enabledListingOthersIdsPacks = array_chunk(array_unique($enabledListingOthersIds), 1000);

            foreach ($enabledListingOthersIdsPacks as $enabledListingOthersIdsPack) {
                $this->resourceConnection->getConnection()->update(
                    $this->resourceConnection->getTableName('m2epro_amazon_listing_other'),
                    array('is_repricing_disabled' => 0),
                    array('listing_other_id IN (?)' => $enabledListingOthersIdsPack)
                );
            }
        }
    }

    //########################################
}