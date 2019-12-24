<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Repricing\Action\Product
 */
class Product extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    protected $resourceCatalogProduct;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $resourceCatalogProduct,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceCatalogProduct = $resourceCatalogProduct;
        parent::__construct($activeRecordFactory, $amazonFactory, $resourceConnection, $helperFactory, $modelFactory);
    }

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
            $result = $this->getHelper('Component_Amazon_Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_DATA_GET_RESPONSE,
                [
                    'response_token' => $responseToken
                ]
            );
        } catch (\Exception $exception) {
            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception, false);
            return false;
        }

        $this->processErrorMessages($result['response']);
        return $result['response'];
    }

    //########################################

    private function sendData($command, array $offersData, $backUrl)
    {
        if (empty($offersData)) {
            return false;
        }

        try {
            $result = $this->getHelper('Component_Amazon_Repricing')->sendRequest(
                $command,
                [
                    'request' => [
                        'auth' => [
                            'account_token' => $this->getAmazonAccountRepricing()->getToken()
                        ],
                        'back_url' => [
                            'url'    => $backUrl,
                            'params' => []
                        ]
                    ],
                    'data' => $this->getHelper('Data')->jsonEncode([
                        'offers' => $offersData,
                    ])
                ]
            );
        } catch (\Exception $exception) {
            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        $response = $result['response'];
        $this->processErrorMessages($response);

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
        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->joinLeft(
            ['l' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
            'l.id = main_table.listing_id',
            ['store_id']
        );

        $nameAttribute = $this->resourceCatalogProduct->getAttribute('name');

        $storeIdSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_product_entity_varchar'),
                new \Zend_Db_Expr('MAX(`store_id`)')
            )
            ->where("`entity_id` = `main_table`.`product_id`")
            ->where("`attribute_id` = ?", $nameAttribute->getAttributeId())
            ->where("`store_id` = 0 OR `store_id` = `l`.`store_id`");

        $listingProductCollection->joinInner(
            [
                'cpe' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_entity')
            ],
            '(cpe.entity_id = `main_table`.product_id)',
            []
        );
        $listingProductCollection->joinInner(
            [
                'cpev' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_entity_varchar')
            ],
            "cpev.entity_id = cpe.entity_id",
            ['product_title' => 'value']
        );

        $listingProductCollection->getSelect()
            ->where('`cpev`.`attribute_id` = ?', $nameAttribute->getAttributeId())
            ->where('`cpev`.`store_id` = ('.$storeIdSelect->__toString().')');

        if ($alreadyOnRepricing) {
            $listingProductCollection->addFieldToFilter('second_table.is_repricing', 1);
        } else {
            $listingProductCollection->addFieldToFilter('second_table.is_repricing', 0);
        }

        $listingProductCollection->addFieldToFilter('main_table.id', ['in' => $listingProductIds]);
        $listingProductCollection->addFieldToFilter('second_table.is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => true]);
        $listingProductCollection->addFieldToFilter('second_table.online_regular_price', ['notnull' => true]);

        if ($listingProductCollection->getSize() <= 0) {
            return [];
        }

        $repricingCollection = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
            ->getCollection();
        $repricingCollection->addFieldToFilter(
            'listing_product_id',
            ['in' => $listingProductCollection->getColumnValues('id')]
        );

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $listingProductCollection->getItems();

        $offersData = [];

        foreach ($listingsProducts as $listingProduct) {
            $listingProductRepricingObject = $repricingCollection->getItemById($listingProduct->getId());

            if ($listingProductRepricingObject === null) {
                $listingProductRepricingObject = $this->activeRecordFactory->getObject(
                    'Amazon_Listing_Product_Repricing'
                );
            }

            $listingProductRepricingObject->setListingProduct($listingProduct);

            $regularPrice = $listingProductRepricingObject->getRegularPrice();
            $minPrice     = $listingProductRepricingObject->getMinPrice();
            $maxPrice     = $listingProductRepricingObject->getMaxPrice();

            $isDisabled   = $listingProductRepricingObject->isDisabled();

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $offersData[] = [
                'name'  => $listingProduct->getData('product_title'),
                'asin'  => $amazonListingProduct->getGeneralId(),
                'sku'   => $amazonListingProduct->getSku(),
                'price' => $amazonListingProduct->getOnlineRegularPrice(),
                'regular_product_price'   => $regularPrice,
                'minimal_product_price'   => $minPrice,
                'maximal_product_price'   => $maxPrice,
                'is_calculation_disabled' => $isDisabled,
            ];
        }

        return $offersData;
    }

    //########################################
}
