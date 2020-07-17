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
    const NICK = 'magento/product/detect_directly_added';

    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
    ) {

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
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
            0 => $productCategories // website for admin values
        ];

        foreach ($product->getWebsiteIds() as $websiteId) {
            $categoriesByWebsite[$websiteId] = $productCategories;
        }

        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category $autoActionsCategory */
        $autoActionsCategory = $this->modelFactory->getObject('Listing_Auto_Actions_Mode_Category');
        $autoActionsCategory->setProduct($product);

        foreach ($categoriesByWebsite as $websiteId => $categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $autoActionsCategory->synchWithAddedCategoryId($categoryId, $websiteId);
            }
        }
    }

    protected function processGlobalActions(\Magento\Catalog\Model\Product $product)
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\GlobalMode $object */
        $object = $this->modelFactory->getObject('Listing_Auto_Actions_Mode_GlobalMode');
        $object->setProduct($product);
        $object->synch();
    }

    protected function processWebsiteActions(\Magento\Catalog\Model\Product $product)
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Website $object */
        $object = $this->modelFactory->getObject('Listing_Auto_Actions_Mode_Website');
        $object->setProduct($product);

        // website for admin values
        $websiteIds = $product->getWebsiteIds();
        $websiteIds[] = 0;

        foreach ($websiteIds as $websiteId) {
            $object->synchWithAddedWebsiteId($websiteId);
        }
    }

    //########################################

    protected function getLastProductId()
    {
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->order('entity_id DESC')->limit(1);
        return (int)$collection->getLastItem()->getId();
    }

    protected function getProducts()
    {
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
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
