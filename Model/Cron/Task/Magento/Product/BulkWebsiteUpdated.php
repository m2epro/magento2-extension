<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Magento\Product\BulkWebsiteUpdated
 */
class BulkWebsiteUpdated extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'magento/product/bulk_website_updated';

    /**
     * @var int (in seconds)
     */
    protected $interval = 600;

    const PRODUCTS_COUNT = 1000;

    protected $productFactory;
    protected $websiteCollectionFactory;
    protected $storeFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
    ) {
        $this->productFactory = $productFactory;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->storeFactory = $storeFactory;
        parent::__construct(
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

    //########################################

    public function performActions()
    {
        $updatingProductsIds = $this->getUpdatingProductsIds();
        $updatedProductsData = $this->getUpdatedProductsData($updatingProductsIds);

        foreach ($updatedProductsData as $productId => $updateProductData) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create()->load($productId);

            /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Website $autoActions */
            $autoActions = $this->modelFactory->getObject('Listing_Auto_Actions_Mode_Website');
            $autoActions->setProduct($product);

            $addAction = \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_ADD;
            $removeAction = \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_REMOVE;

            foreach ($updateProductData[$addAction] as $websiteId) {
                if (in_array($websiteId, $product->getWebsiteIds())) {
                    $autoActions->synchWithAddedWebsiteId($websiteId);
                }
            }

            foreach ($updateProductData[$removeAction] as $websiteId) {
                if (!in_array($websiteId, $product->getWebsiteIds())) {
                    $autoActions->synchWithDeletedWebsiteId($websiteId);
                }
            }
        }

        $this->removeProcessingWebsiteUpdatesForProducts($updatingProductsIds);
    }

    //########################################

    protected function getUpdatingProductsIds()
    {
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_magento_product_websites_update');

        $limit = self::PRODUCTS_COUNT;

        $tempQuery = <<<SQL
SELECT DISTINCT
  `product_id`
FROM (SELECT
    `main_table`.`product_id`
  FROM `{$table}` AS `main_table`
  ORDER BY `main_table`.`id` ASC) AS `t`
LIMIT {$limit};
SQL;

        return $this->resource->getConnection()->query($tempQuery)->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function removeProcessingWebsiteUpdatesForProducts($productsIds)
    {
        $this->resource->getConnection()->delete(
            $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_magento_product_websites_update'),
            ['product_id IN (?)' => $productsIds]
        );
    }

    protected function getUpdatedProductsData($updatingProductsIds)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Websites\Update\Collection $websiteUpdates */
        $websiteUpdates = $this->activeRecordFactory->getObject('Magento_Product_Websites_Update')->getCollection();
        $websiteUpdates->getSelect()->where('product_id IN (?)', $updatingProductsIds);

        $actionAdd = \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_ADD;
        $actionRemove = \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_REMOVE;

        $addedWebsiteIds = [];
        $deletedWebsiteIds = [];
        $updatedProductsData = [];

        foreach ($websiteUpdates->getItems() as $websiteUpdate) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Websites\Update $websiteUpdate */

            if (empty($updatedProductsData[$websiteUpdate->getProductId()])) {
                $updatedProductsData[$websiteUpdate->getProductId()] = [
                    $actionAdd => [],
                    $actionRemove => []
                ];
            }

            $updatedProductData = &$updatedProductsData[$websiteUpdate->getProductId()];

            if ($websiteUpdate->getAction() == $actionAdd) {
                $updatedProductData[$actionAdd][] = $websiteUpdate->getWebsiteId();
                $addedWebsiteIds[] = $websiteUpdate->getWebsiteId();
            } else {
                $updatedProductData[$actionRemove][] = $websiteUpdate->getWebsiteId();
                $deletedWebsiteIds[] = $websiteUpdate->getWebsiteId();
            }
        }

        $addedWebsiteIds = array_unique($addedWebsiteIds);
        $deletedWebsiteIds = array_unique($deletedWebsiteIds);

        $addedWebsitesWithListings = $this->getWebsitesWithListingByAction($addedWebsiteIds, $actionAdd);
        $deletedWebsitesWithListings = $this->getWebsitesWithListingByAction($deletedWebsiteIds, $actionRemove);

        foreach ($updatedProductsData as $productId => &$updatedProductData) {
            $addedWebsiteIds = &$updatedProductData[$actionAdd];
            $deletedWebsiteIds = &$updatedProductData[$actionRemove];

            $addedWebsiteIds = array_intersect($addedWebsiteIds, $addedWebsitesWithListings);
            $deletedWebsiteIds = array_intersect($deletedWebsiteIds, $deletedWebsitesWithListings);

            if (empty($addedWebsiteIds) && empty($deletedWebsiteIds)) {
                unset($updatedProductsData[$productId]);
            }
        }

        return $updatedProductsData;
    }

    //########################################

    protected function getWebsitesWithListingByAction($websiteIds, $action)
    {
        $websitesWithListings = [];

        if (empty($websiteIds)) {
            return $websitesWithListings;
        }

        $websitesCollection = $this->websiteCollectionFactory->create()
            ->addFieldToFilter('website_id', ['in' => $websiteIds]);

        $websitesCollection->getSelect()->joinLeft(
            ['cs' => $this->storeFactory->create()->getResource()->getMainTable()],
            '(`cs`.`website_id` = `main_table`.`website_id`)'
        );

        if ($action == \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_ADD) {
            $websitesCollection->getSelect()->joinLeft(
                ['ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                '(`ml`.`store_id` = `cs`.`store_id` AND `ml`.`auto_website_adding_mode` != ' .
                    \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE . ')',
                [
                    'listing_id' => 'id'
                ]
            );
        } elseif ($action == \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_REMOVE) {
            $websitesCollection->getSelect()->joinLeft(
                ['ml' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                '(`ml`.`store_id` = `cs`.`store_id` AND `ml`.`auto_website_deleting_mode` != ' .
                    \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE . ')',
                [
                    'listing_id' => 'id'
                ]
            );
        }

        $websites = $this->resource->getConnection()->query($websitesCollection->getSelect())->fetchAll();

        foreach ($websites as $website) {
            if ($website['listing_id'] !== null) {
                $websitesWithListings[] = $website['website_id'];
            }
        }

        return array_unique($websitesWithListings);
    }

    //########################################
}
