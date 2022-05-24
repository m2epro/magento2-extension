<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class DetectSpecialPriceEndDate extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'magento/product/detect_special_price_end_date';

    /** @var int (in seconds) */
    protected $interval = 7200;

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;

    protected $listingProductCollectionFactory;
    protected $catalogProductCollectionFactory;
    protected $listingCollectionFactory;

    /** @var \Ess\M2ePro\Helper\Module */
    private $module;

    public function __construct(
        \Ess\M2ePro\Helper\Module $module,
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $catalogProductCollectionFactory,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->publicService = $publicService;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->module = $module;
    }

    //########################################

    protected function performActions()
    {
        if ($this->getLastProcessedProductId() === null) {
            $this->setLastProcessedProductId(0);
        }

        $changedProductsPrice = $this->getAllChangedProductsPrice();

        if (!$changedProductsPrice) {
            $this->setLastProcessedProductId(0);
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', ['in' => array_keys($changedProductsPrice)]);
        $collection->addFieldToFilter('status', ['neq' => 0]);

        /** @var  \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $currentPrice = (float)$this->getCurrentPrice($listingProduct);
            $newPrice = (float)$changedProductsPrice[$listingProduct->getProductId()]['price'];

            if ($currentPrice == $newPrice) {
                continue;
            }

            $this->publicService->markPriceChanged($listingProduct->getProductId());
        }

        $this->publicService->applyChanges();

        $lastMagentoProduct = $this->getArrayKeyLast($changedProductsPrice);
        $this->setLastProcessedProductId((int)$lastMagentoProduct);
    }

    //########################################

    protected function getArrayKeyLast($array)
    {
        if (!is_array($array) || empty($array)) {
            return NULL;
        }

        $arrayKeys = array_keys($array);
        return $arrayKeys[count($array)-1];
    }

    protected function getCurrentPrice(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if ($listingProduct->isComponentModeAmazon()) {
            return $listingProduct->getChildObject()->getOnlineRegularPrice();
        } elseif ($listingProduct->isComponentModeEbay()) {
            return $listingProduct->getChildObject()->getOnlineCurrentPrice();
        } elseif ($listingProduct->isComponentModeWalmart()) {
            return $listingProduct->getChildObject()->getOnlinePrice();
        } else {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component Mode is not defined.');
        }
    }

    //########################################

    protected function getAllStoreIds()
    {
        $storeIds = [];

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collectionListing */
        $collectionListing = $this->listingCollectionFactory->create();
        $collectionListing->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collectionListing->getSelect()->columns(['store_id' => 'store_id']);
        $collectionListing->getSelect()->group('store_id');

        foreach ($collectionListing->getData() as $item){
            $storeIds[] = $item['store_id'];
        }

        return $storeIds;
    }

    protected function getChangedProductsPrice($storeId)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('-1 day');

        $collection = $this->catalogProductCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToFilter('special_price', ['notnull' => true]);
        $collection->addFieldToFilter('special_to_date', ['notnull' => true]);
        $collection->addFieldToFilter('special_to_date', ['lt' => $date->format('Y-m-d H:i:s')]);
        $collection->addFieldToFilter('entity_id', ['gt' => (int)$this->getLastProcessedProductId()]);
        $collection->setOrder('entity_id', 'asc');
        $collection->getSelect()->limit(1000);

        return $collection->getItems();
    }

    protected function getAllChangedProductsPrice()
    {
        $changedProductsPrice = [];

        /** @var \Magento\Catalog\Model\Product $magentoProduct */
        foreach ($this->getAllStoreIds() as $storeId) {
            foreach ($this->getChangedProductsPrice($storeId) as $magentoProduct) {
                $changedProductsPrice[$magentoProduct->getId()] = [
                    'price' => $magentoProduct->getPrice()
                ];
            }
        }

        ksort($changedProductsPrice);

        return array_slice($changedProductsPrice, 0, 1000, true);
    }

    // ---------------------------------------

    protected function getLastProcessedProductId()
    {
        return $this->module->getRegistry()->getValue(
            '/magento/product/detect_special_price_end_date/last_magento_product_id/'
        );
    }

    protected function setLastProcessedProductId($magentoProductId)
    {
        $this->module->getRegistry()->setValue(
            '/magento/product/detect_special_price_end_date/last_magento_product_id/',
            (int)$magentoProductId
        );
    }

    //########################################
}
