<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore\State;

class Updater extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = NULL;

    /** @var \Ess\M2ePro\Model\Listing\Product\Variation[] $variations */
    private $variations = array();

    private $maxAppliedQtyValue = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore\QtyCalculator */
    private $qtyCalculator = NULL;

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore[] $accountPickupStores */
    private $accountPickupStores = array();

    /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore\State[] $accountPickupStoreStateItems */
    private $accountPickupStoreStateItems = array();

    protected $resourceConnection;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    public function setMaxAppliedQtyValue($value)
    {
        $this->maxAppliedQtyValue = $value;
        return $this;
    }

    public function getMaxAppliedQtyValue()
    {
        return $this->maxAppliedQtyValue;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    public function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    //########################################

    public function process()
    {
        $affectedItemsCount = 0;

        if (!$this->getListingProduct()->isListed()) {
            return $affectedItemsCount;
        }

        $calculatedValues = $this->calculateValues();
        if (empty($calculatedValues)) {
            return $affectedItemsCount;
        }

        $affectedItemsCount = $this->applyCalculatedValues($this->prepareCalculatedValues($calculatedValues));

        $connection = $this->resourceConnection->getConnection();

        if (!$this->isDeleted()) {
            $connection->update(
                $this->resourceConnection->getTableName('m2epro_ebay_listing_product_pickup_store'),
                array('is_process_required' => 0),
                array('listing_product_id = ?' => $this->getListingProduct()->getId())
            );
        }

        return $affectedItemsCount;
    }

    //########################################

    private function calculateValues()
    {
        $calculatedValues = array();

        foreach ($this->getSkus() as $sku) {
            $fullSettingsCache   = array();
            $sourceSettingsCache = array();

            foreach ($this->getAccountPickupStores() as $accountPickupStore) {
                $fullSettings = $accountPickupStore->getQtySource();
                if ($accountPickupStore->isQtyModeSellingFormatTemplate()) {
                    $fullSettings = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtySource();
                }

                $fullSettingsHash = md5($this->getHelper('Data')->jsonEncode($fullSettings));
                if (isset($fullSettingsCache[$fullSettingsHash])) {
                    $calculatedValues[] = array(
                        'sku' => $sku,
                        'account_pickup_store_id' => $accountPickupStore->getId(),
                        'qty' => $fullSettingsCache[$fullSettingsHash],
                    );
                    continue;
                }

                $sourceSettings = array(
                    'mode'      => $fullSettings['mode'],
                    'value'     => $fullSettings['value'],
                    'attribute' => $fullSettings['attribute'],
                );

                $sourceSettingsHash = md5($this->getHelper('Data')->jsonEncode($sourceSettings));

                $bufferedValue = NULL;
                if (isset($sourceSettingsCache[$sourceSettingsHash])) {
                    $bufferedValue = $sourceSettingsCache[$sourceSettingsHash];
                } else {
                    $bufferedValue = $this->calculateClearQty($sku, $accountPickupStore);
                    $sourceSettingsCache[$sourceSettingsHash] = $bufferedValue;
                }

                $calculatedQty = $this->calculateQty($sku, $accountPickupStore, $bufferedValue);
                $fullSettingsCache[$fullSettingsHash] = $calculatedQty;

                $calculatedValues[] = array(
                    'sku' => $sku,
                    'account_pickup_store_id' => $accountPickupStore->getId(),
                    'qty' => $calculatedQty,
                );
            }
        }

        return $calculatedValues;
    }

    private function prepareCalculatedValues(array $calculatedValues)
    {
        $preparedUpdateValues = array();
        $preparedCreateValues = array();

        foreach ($calculatedValues as $calculatedValue) {
            $stateItem = $this->getAccountPickupStoreStateItem(
                $calculatedValue['sku'], $calculatedValue['account_pickup_store_id']
            );

            if (is_null($stateItem)) {
                $preparedCreateValues[] = array(
                    'sku'                     => $calculatedValue['sku'],
                    'account_pickup_store_id' => $calculatedValue['account_pickup_store_id'],
                    'online_qty'              => 0,
                    'target_qty'              => $calculatedValue['qty'],
                    'is_added'                => 1,
                    'is_deleted'              => 0,
                    'update_date'             => $this->getHelper('Data')->getCurrentGmtDate(),
                    'create_date'             => $this->getHelper('Data')->getCurrentGmtDate(),
                );
                continue;
            }

            if ($stateItem->getTargetQty() == $calculatedValue['qty']) {
                continue;
            }

            if (is_null($this->getMaxAppliedQtyValue())) {
                $preparedUpdateValues[$calculatedValue['qty']][] = array(
                    'sku' => $calculatedValue['sku'],
                    'account_pickup_store_id' => $calculatedValue['account_pickup_store_id'],
                );

                continue;
            }

            if ($calculatedValue['qty'] > $this->getMaxAppliedQtyValue() &&
                $stateItem->getOnlineQty() > $this->getMaxAppliedQtyValue()
            ) {
                continue;
            }

            $preparedUpdateValues[$calculatedValue['qty']][] = array(
                'sku' => $calculatedValue['sku'],
                'account_pickup_store_id' => $calculatedValue['account_pickup_store_id'],
            );
        }

        return array(
            'create' => $preparedCreateValues,
            'update' => $preparedUpdateValues,
        );
    }

    private function applyCalculatedValues(array $calculatedValues)
    {
        $connection = $this->resourceConnection->getConnection();

        $affectedItemsCount = 0;

        if (!empty($calculatedValues['create'])) {
            $connection->insertMultiple(
                $this->resourceConnection->getTableName('m2epro_ebay_account_pickup_store_state'),
                $calculatedValues['create']
            );

            $affectedItemsCount += count($calculatedValues['create']);
        }

        foreach ($calculatedValues['update'] as $qty => $filters) {
            $where = '';
            foreach ($filters as $filter) {
                if (!empty($where)) {
                    $where .= ' OR ';
                }

                $filterString = 'sku = \''.$filter['sku'].'\' ';
                $filterString .= 'AND account_pickup_store_id = '.$filter['account_pickup_store_id'];

                $where .= '('.$filterString.')';
            }

            $connection->update(
                $this->resourceConnection->getTableName('m2epro_ebay_account_pickup_store_state'),
                array('target_qty' => $qty, 'update_date' => $this->getHelper('Data')->getCurrentGmtDate()),
                $where
            );

            $affectedItemsCount += count($filters);
        }

        return $affectedItemsCount;
    }

    //########################################

    private function isDeleted()
    {
        $skus = $this->getSkus();

        if (empty($skus)) {
            return false;
        }

        foreach ($skus as &$sku) {
            $sku = $this->resourceConnection->getConnection()->quote($sku);
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')->getCollection();
        $collection->addFieldToFilter('main_table.listing_product_id', $this->getListingProduct()->getId());
        $collection->getSelect()->join(
            ['eaps' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')
                ->getResource()->getMainTable()],
            'eaps.account_pickup_store_id=main_table.account_pickup_store_id
            AND eaps.sku IN(' . implode(',', $skus) . ') AND eaps.is_deleted = 1',
            ['state_id' => 'id']
        );

        return $collection->getSize();
    }

    //########################################

    private function calculateQty($sku, \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore,
                                  $bufferedValue = NULL)
    {
        if (!$this->getEbayListingProduct()->isVariationsReady()) {
            return $this->getQtyCalculator()->getLocationProductValue($accountPickupStore, $bufferedValue);
        }

        return $this->getQtyCalculator()->getLocationVariationValue(
            $this->getVariation($sku), $accountPickupStore, $bufferedValue
        );
    }

    private function calculateClearQty($sku, \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore)
    {
        if (!$this->getEbayListingProduct()->isVariationsReady()) {
            return $this->getQtyCalculator()->getClearLocationProductValue($accountPickupStore);
        }

        return $this->getQtyCalculator()->getClearLocationVariationValue(
            $this->getVariation($sku), $accountPickupStore
        );
    }

    //########################################

    private function getSkus()
    {
        $skus = array();

        if ($this->getEbayListingProduct()->isVariationsReady()) {
            foreach ($this->getVariations() as $variation) {

                /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation->getChildObject();

                $onlineSku = $ebayVariation->getOnlineSku();
                if (empty($onlineSku)) {
                    continue;
                }

                $skus[] = $onlineSku;
            }
        } else {
            $onlineSku = $this->getEbayListingProduct()->getOnlineSku();
            if (!empty($onlineSku)) {
                $skus[] = $onlineSku;
            }
        }

        return $skus;
    }

    // ---------------------------------------

    private function getAccountPickupStores()
    {
        if (!empty($this->accountPickupStores)) {
            return $this->accountPickupStores;
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')->getCollection();
        $collection->addFieldToFilter('listing_product_id', $this->getListingProduct()->getId());

        $accountPickupStoreIds = array_unique($collection->getColumnValues('account_pickup_store_id'));
        if (empty($accountPickupStoreIds)) {
            return $this->accountPickupStores = array();
        }

        $accountPickupStoreCollection = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore')
            ->getCollection();
        $accountPickupStoreCollection->addFieldToFilter('id', array('in' => $accountPickupStoreIds));

        return $this->accountPickupStores = $accountPickupStoreCollection->getItems();
    }

    // ---------------------------------------

    private function getAccountPickupStoreStateItems()
    {
        if (!empty($this->accountPickupStoreStateItems)) {
            return $this->accountPickupStoreStateItems;
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')->getCollection();
        $collection->addFieldToFilter('sku', array('in' => $this->getSkus()));

        return $this->accountPickupStoreStateItems = $collection->getItems();
    }

    private function getAccountPickupStoreStateItem($sku, $accountPickupStoreId)
    {
        foreach ($this->getAccountPickupStoreStateItems() as $stateItem) {
            if ($stateItem->getSku() != $sku) {
                continue;
            }

            if ($stateItem->getAccountPickupStoreId() != $accountPickupStoreId) {
                continue;
            }

            return $stateItem;
        }

        return NULL;
    }

    // ---------------------------------------

    private function getVariations()
    {
        if (!empty($this->variations)) {
            return $this->variations;
        }

        return $this->variations = $this->getListingProduct()->getVariations(
            true, array('status' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
        );
    }

    private function getVariation($sku)
    {
        foreach ($this->getVariations() as $variation) {
            if ($variation->getChildObject()->getOnlineSku() != $sku) {
                continue;
            }

            return $variation;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('SKU not found.');
    }

    //########################################

    private function getQtyCalculator()
    {
        if (!is_null($this->qtyCalculator)) {
            return $this->qtyCalculator;
        }

        $this->qtyCalculator = $this->modelFactory->getObject('Ebay\Listing\Product\PickupStore\QtyCalculator');
        $this->qtyCalculator->setProduct($this->getListingProduct());

        return $this->qtyCalculator;
    }

    //########################################
}