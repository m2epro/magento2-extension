<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\MSI\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class SourceItem
 * @package Ess\M2ePro\Observer\MSI
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class Save extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface|null */
    private $beforeSourceItem;
    /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface */
    private $afterSourceItem;
    /** @var GetAssignedStockIdForWebsiteInterface */
    private $assignedStockIdForWebsite;
    /** @var GetAssignedSalesChannelsForStockInterface */
    private $getAssignedChannels;
    /** @var DefaultSourceProviderInterface */
    private $defaultSourceProvider;
    /** @var WebsiteRepositoryInterface */
    private $websiteRepository;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var ResourceConnection */
    private $resource;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;
    private $affectedListingsProducts = [];
    private $productId;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\ObjectManagerInterface $objectManager,
                                \Magento\Catalog\Model\ResourceModel\Product $productResource,
                                ResourceConnection $resourceConnection,
                                WebsiteRepositoryInterface $websiteRepository)
    {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->objectManager             = $objectManager;
        $this->resource                  = $resourceConnection;
        $this->productResource           = $productResource;
        $this->websiteRepository         = $websiteRepository;
        $this->assignedStockIdForWebsite = $this->objectManager->get(GetAssignedStockIdForWebsiteInterface::class);
        $this->getAssignedChannels       = $this->objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
        $this->defaultSourceProvider     = $this->objectManager->get(DefaultSourceProviderInterface::class);
    }

    //########################################

    /**
     * @return bool
     */
    public function canProcess()
    {
        /**
         * If Default Source item is processed, \Ess\M2ePro\Observer\StockItem\Save\After will handle the changes
         */
        if ($this->getEvent()->getAfterItem()->getSourceCode() === $this->defaultSourceProvider->getCode()) {
            return false;
        }

        return true;
    }

    public function process()
    {
        $this->beforeSourceItem = $this->getEvent()->getBeforeItem();
        $this->afterSourceItem  = $this->getEvent()->getAfterItem();

        if (!$this->areThereAffectedItems()) {
            return;
        }

        $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
            $this->getProductId(),
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );

        $this->processQty();
        $this->processStockAvailability();
    }

    private function processQty()
    {
        if (!is_null($this->beforeSourceItem)) {
            $oldValue = (int)$this->beforeSourceItem->getQuantity();
        } else {
            $oldValue = 'undefined';
        }

        $newValue = (int)$this->afterSourceItem->getQuantity();

        if ($oldValue == $newValue || !$this->updateProductChangeRecord('qty',$oldValue,$newValue)) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                $oldValue,
                $newValue
            );
        }
    }

    private function processStockAvailability()
    {
        // M2ePro\TRANSLATIONS
        // IN Stock
        // OUT of Stock

        if (!is_null($this->beforeSourceItem)) {
            $oldValue = (bool)$this->beforeSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';
        } else {
            $oldValue = 'undefined';
        }

        $newValue = (bool)$this->afterSourceItem->getStatus() ? 'IN Stock' : 'OUT of Stock';

        if ($oldValue == $newValue || !$this->updateProductChangeRecord('stock_availability',$oldValue,$newValue)) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY,
                $oldValue,
                $newValue
            );
        }
    }

    /**
     * @param $attributeCode
     * @param $oldValue
     * @param $newValue
     * @return mixed
     */
    private function updateProductChangeRecord($attributeCode, $oldValue, $newValue)
    {
        return $this->activeRecordFactory->getObject('ProductChange')->updateAttribute(
            $this->getProductId(),
            $attributeCode,
            $oldValue,
            $newValue,
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );
    }

    //########################################

    /**
     * @return bool
     */
    private function areThereAffectedItems()
    {
        return count($this->getAffectedListingsProducts()) > 0;
    }

    /**
     * @return array
     */
    private function getAffectedListingsProducts()
    {
        if (!empty($this->affectedListingsProducts)) {
            return $this->affectedListingsProducts;
        }

        $stockIds = $this->resource
                         ->getConnection()
                         ->select()
                         ->from($this->resource->getTableName('inventory_source_stock_link'), 'stock_id')
                         ->where('source_code = ?', $this->afterSourceItem->getSourceCode())
                         ->query()
                         ->fetchAll(\PDO::FETCH_COLUMN);

        $mergedStoreIds = [];

        foreach ($stockIds as $stockId) {
            $channels = $this->getAssignedChannels->execute($stockId);
            foreach ($channels as $channel) {
                $website = $this->websiteRepository->get($channel->getCode());
                $mergedStoreIds = array_merge($mergedStoreIds, $website->getStoreIds());

                if ($website->getIsDefault()) {
                    $mergedStoreIds[] = 0;
                }
            }
        }

        if (empty($mergedStoreIds)) {
            return $this->affectedListingsProducts;
        }

        return $this->affectedListingsProducts = $this->activeRecordFactory
                                                      ->getObject('Listing\Product')
                                                      ->getResource()
                                                      ->getItemsByProductId(
                                                          $this->getProductId(),
                                                          array('store_id' => $mergedStoreIds)
                                                      );
    }

    //########################################

    /**
     * @return false|int
     */
    private function getProductId()
    {
        if (!empty($this->productId)) {
            return $this->productId;
        }

        return $this->productId = $this->productResource->getIdBySku($this->afterSourceItem->getSku());
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param $action
     * @param $oldValue
     * @param $newValue
     */
    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, $action,
                                              $oldValue, $newValue)
    {
        // M2ePro\TRANSLATIONS
        // From [%from%] to [%to%].

        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            $action,
            $this->getHelper('Module\Log')->encodeDescription(
                'Value was changed from [%from%] to [%to%] in the "%source%" Source.',
                array('!from'=>$oldValue,'!to'=>$newValue,'!source'=>$this->afterSourceItem->getSourceCode())
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}