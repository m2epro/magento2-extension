<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask\MagentoProducts;

class AddedProducts extends AbstractModel
{
    private $productFactory;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->productFactory = $productFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/added_products/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Auto Add Rules';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 70;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 80;
    }

    //########################################

    protected function performActions()
    {
        if (is_null($this->getLastProcessedProductId())) {
            $this->setLastProcessedProductId($this->getLastProductId());
        }

        if (count($products = $this->getProducts()) <= 0) {
            return;
        }

        $tempIndex = 0;
        $totalItems = count($products);

        foreach ($products as $product) {

            $this->processCategoriesActions($product);
            $this->processGlobalActions($product);
            $this->processWebsiteActions($product);

            if ((++$tempIndex)%20 == 0) {
                $percentsPerOneItem = $this->getPercentsInterval()/$totalItems;
                $this->getActualLockItem()->setPercents($percentsPerOneItem*$tempIndex);
                $this->getActualLockItem()->activate();
            }
        }

        $lastMagentoProduct = array_pop($products);
        $this->setLastProcessedProductId((int)$lastMagentoProduct->getId());
    }

    //########################################

    private function processCategoriesActions(\Magento\Catalog\Model\Product $product)
    {
        $productCategories = $product->getCategoryIds();

        $categoriesByWebsite = array(
            0 => $productCategories // website for admin values
        );

        foreach ($product->getWebsiteIds() as $websiteId) {
            $categoriesByWebsite[$websiteId] = $productCategories;
        }

        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category $autoActionsCategory */
        $autoActionsCategory = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\Category');
        $autoActionsCategory->setProduct($product);

        foreach ($categoriesByWebsite as $websiteId => $categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $autoActionsCategory->synchWithAddedCategoryId($categoryId, $websiteId);
            }
        }
    }

    private function processGlobalActions(\Magento\Catalog\Model\Product $product)
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\GlobalMode $object */
        $object = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\GlobalMode');
        $object->setProduct($product);
        $object->synch();
    }

    private function processWebsiteActions(\Magento\Catalog\Model\Product $product)
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Website $object */
        $object = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\Website');
        $object->setProduct($product);

        // website for admin values
        $websiteIds = $product->getWebsiteIds();
        $websiteIds[] = 0;

        foreach ($websiteIds as $websiteId) {
            $object->synchWithAddedWebsiteId($websiteId);
        }
    }

    //########################################

    private function getLastProductId()
    {
        $collection = $this->productFactory->create()->getCollection();
        $collection->getSelect()->order('entity_id DESC')->limit(1);
        return (int)$collection->getLastItem()->getId();
    }

    private function getProducts()
    {
        $collection = $this->productFactory->create()->getCollection();

        $collection->addFieldToFilter('entity_id', array('gt' => (int)$this->getLastProcessedProductId()));
        $collection->addAttributeToSelect('visibility');
        $collection->setOrder('entity_id','asc');
        $collection->getSelect()->limit(100);

        return $collection->getItems();
    }

    // ---------------------------------------

    private function getLastProcessedProductId()
    {
        return $this->getConfigValue($this->getFullSettingsPath(),'last_magento_product_id');
    }

    private function setLastProcessedProductId($magentoProductId)
    {
        $this->setConfigValue($this->getFullSettingsPath(),'last_magento_product_id',(int)$magentoProductId);
    }

    //########################################
}