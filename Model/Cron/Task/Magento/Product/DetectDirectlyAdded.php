<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyAdded
 */
class DetectDirectlyAdded extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'magento/product/detect_directly_added';

    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory  */
    protected $magentoProductCollectionFactory;

    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory */
    private $listingAutoActionsModeFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory $listingAutoActionsModeFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->listingAutoActionsModeFactory = $listingAutoActionsModeFactory;
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
    }

    //########################################

    protected function performActions()
    {
        if ($this->getLastProcessedProductId() === null) {
            $this->setLastProcessedProductId($this->getLastProductId());
        }

        if (empty($products = $this->getProducts())) {
            return;
        }

        foreach ($products as $product) {
            $this->processCategoriesActions($product);
            $this->processGlobalActions($product);
            $this->processWebsiteActions($product);
        }

        $lastMagentoProduct = array_pop($products);
        $this->setLastProcessedProductId((int)$lastMagentoProduct->getId());
    }

    //########################################

    protected function processCategoriesActions(\Magento\Catalog\Model\Product $product)
    {
        $productCategories = $product->getCategoryIds();

        $categoriesByWebsite = [
            0 => $productCategories, // website for admin values
        ];

        foreach ($product->getWebsiteIds() as $websiteId) {
            $categoriesByWebsite[$websiteId] = $productCategories;
        }

        $autoActionsCategory = $this->listingAutoActionsModeFactory->createCategoryMode($product);
        foreach ($categoriesByWebsite as $websiteId => $categoryIds) {
            $autoActionsCategory->synchWithAddedCategoryId($websiteId, $categoryIds);
        }
    }

    protected function processGlobalActions(\Magento\Catalog\Model\Product $product)
    {
        $globalMode = $this->listingAutoActionsModeFactory->createGlobalMode($product);
        $globalMode->synch();
    }

    protected function processWebsiteActions(\Magento\Catalog\Model\Product $product)
    {
        $websiteMode = $this->listingAutoActionsModeFactory->createWebsiteMode($product);

        // website for admin values
        $websiteIds = $product->getWebsiteIds();
        $websiteIds[] = 0;

        foreach ($websiteIds as $websiteId) {
            $websiteMode->synchWithAddedWebsiteId($websiteId);
        }
    }

    //########################################

    protected function getLastProductId()
    {
        /* @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->order('entity_id DESC')->limit(1);

        return (int)$collection->getLastItem()->getId();
    }

    protected function getProducts()
    {
        /* @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->addFieldToFilter('entity_id', ['gt' => (int)$this->getLastProcessedProductId()]);
        $collection->addAttributeToSelect('visibility');
        $collection->setOrder('entity_id', 'asc');
        $collection->getSelect()->limit(100);

        return $collection->getItems();
    }

    // ---------------------------------------

    protected function getLastProcessedProductId()
    {
        return $this->getHelper('Module')->getRegistry()->getValue(
            '/magento/product/detect_directly_added/last_magento_product_id/'
        );
    }

    protected function setLastProcessedProductId($magentoProductId)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/magento/product/detect_directly_added/last_magento_product_id/',
            (int)$magentoProductId
        );
    }

    //########################################
}
