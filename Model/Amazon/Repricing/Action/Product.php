<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Action;

class Product extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    //########################################

    public function sendAddProductsActionData(array $listingsProductsIds, $backUrl)
    {
        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_ADD,
            $this->getOffersData($listingsProductsIds, false),
            $backUrl
        );
    }

    public function sendShowProductsDetailsActionData(array $listingsProductsIds, $backUrl)
    {
        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_DETAILS,
            $this->getOffersData($listingsProductsIds, true),
            $backUrl
        );
    }

    public function sendEditProductsActionData(array $listingsProductsIds, $backUrl)
    {
        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_EDIT,
            $this->getOffersData($listingsProductsIds, true),
            $backUrl
        );
    }

    public function sendRemoveProductsActionData(array $listingsProductsIds, $backUrl)
    {
        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_REMOVE,
            $this->getOffersData($listingsProductsIds, true),
            $backUrl
        );
    }

    //########################################

    public function getActionResponseData($responseToken)
    {
        try {
            $result = $this->getHelper('Component\Amazon\Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_DATA_GET_RESPONSE,
                array(
                    'response_token' => $responseToken
                )
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        return json_decode($result['response'], true);
    }

    //########################################

    private function sendData($command, array $offersData, $backUrl)
    {
        if (empty($offersData)) {
            return false;
        }

        try {
            $result = $this->getHelper('Component\Amazon\Repricing')->sendRequest(
                $command, array(
                    'request' => array(
                        'auth' => array(
                            'account_token' => $this->getAmazonAccountRepricing()->getToken()
                        ),
                        'back_url' => array(
                            'url'    => $backUrl,
                            'params' => array()
                        )
                    ),
                    'data' => json_encode(array(
                        'offers' => $offersData,
                    ))
                )
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        $response = json_decode($result['response'], true);

        return !empty($response['request_token']) ? $response['request_token'] : false;
    }

    //########################################

    /**
     * @param array $listingProductIds
     * @param bool $alreadyOnRepricing
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getOffersData(array $listingProductIds, $alreadyOnRepricing = false)
    {
        $listingProductCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->getSelect()->joinLeft(
            array('l' => $this->resourceConnection->getTableName('m2epro_listing')),
            'l.id = main_table.listing_id',
            array('store_id')
        );

        $storeIdSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $this->resourceConnection->getTableName('catalog_product_entity_varchar'),
                new \Zend_Db_Expr('MAX(`store_id`)')
            )
            ->where("`entity_id` = `main_table`.`product_id`")
            ->where("`attribute_id` = `ea`.`attribute_id`")
            ->where("`store_id` = 0 OR `store_id` = `l`.`store_id`");

        $listingProductCollection->getSelect()
            ->join(
                array('cpev' => $this->resourceConnection->getTableName('catalog_product_entity_varchar')),
                "cpev.entity_id = main_table.product_id",
                array('product_title' => 'value')
            )
            ->join(
                array('ea' => $this->resourceConnection->getTableName('eav_attribute')),
                'cpev.attribute_id = ea.attribute_id AND ea.attribute_code = \'name\'',
                array()
            )
            ->where('cpev.store_id = ('.$storeIdSelect->__toString().')');

        $tableAmazonListingProductRepricing = $this->resourceConnection->getTableName(
            'm2epro_amazon_listing_product_repricing'
        );
        $listingProductCollection->getSelect()->joinLeft(
            array('alpr' => $tableAmazonListingProductRepricing),
            'alpr.listing_product_id = second_table.listing_product_id',
            array()
        );

        if ($alreadyOnRepricing) {
            $listingProductCollection->addFieldToFilter('alpr.listing_product_id', array('notnull' => true));
        } else {
            $listingProductCollection->addFieldToFilter('alpr.listing_product_id', array('null' => true));
        }

        $listingProductCollection->addFieldToFilter('main_table.id', array('in' => $listingProductIds));
        $listingProductCollection->addFieldToFilter('second_table.is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('second_table.sku', array('notnull' => true));

        if ($listingProductCollection->getSize() <= 0) {
            return array();
        }

        $repricingCollection = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
            ->getCollection();
        $repricingCollection->addFieldToFilter(
            'listing_product_id', array('in' => $listingProductCollection->getColumnValues('id'))
        );

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $listingProductCollection->getItems();

        $offersData = array();

        foreach ($listingsProducts as $listingProduct) {
            $listingProductRepricingObject = $repricingCollection->getItemById($listingProduct->getId());

            if (is_null($listingProductRepricingObject)) {
                $listingProductRepricingObject = $this->activeRecordFactory->getObject(
                    'Amazon\Listing\Product\Repricing'
                );
            }

            $listingProductRepricingObject->setListingProduct($listingProduct);

            $regularPrice = $listingProductRepricingObject->getRegularPrice();
            $minPrice     = $listingProductRepricingObject->getMinPrice();
            $maxPrice     = $listingProductRepricingObject->getMaxPrice();

            $isDisabled   = $listingProductRepricingObject->isDisabled();

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $offersData[] = array(
                'name'  => $listingProduct->getData('product_title'),
                'asin'  => $amazonListingProduct->getGeneralId(),
                'sku'   => $amazonListingProduct->getSku(),
                'price' => $amazonListingProduct->getOnlinePrice(),
                'regular_product_price'   => $regularPrice,
                'minimal_product_price'   => $minPrice,
                'maximal_product_price'   => $maxPrice,
                'is_calculation_disabled' => $isDisabled,
            );
        }

        return $offersData;
    }

    //########################################
}