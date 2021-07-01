<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectSpecialPriceEndDate
 */
class DetectSpecialPriceEndDate extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'magento/product/detect_special_price_end_date';

    /**
     * @var int (in seconds)
     */
    protected $interval = 7200;

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange  */
    protected $publicService;

    protected $listingProductCollectionFactory;
    protected $catalogProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $catalogProductCollectionFactory,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
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
    }

    //########################################

    protected function performActions()
    {
        if ($this->getLastProcessedProductId() === null) {
            $this->setLastProcessedProductId($this->getFirstProductId());
        }

        $changedProductsPrice = [];
        $magentoProducts = $this->getChangedProductsPrice();

        /** @var \Magento\Catalog\Model\Product $magentoProduct */
        foreach ($magentoProducts as $magentoProduct) {
            $changedProductsPrice[$magentoProduct->getId()] = [
                'price' => $magentoProduct->getPrice()
            ];
        }

        if (!$changedProductsPrice) {
            $this->setLastProcessedProductId($this->getFirstProductId());
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter('product_id', ['in' => array_keys($changedProductsPrice)]);

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

        $lastMagentoProduct = array_pop($magentoProducts);
        $this->setLastProcessedProductId((int)$lastMagentoProduct->getId());
    }

    //########################################

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

    protected function getFirstProductId()
    {
        $collection = $this->catalogProductCollectionFactory->create();
        $collection->setOrder('entity_id', 'asc');
        $collection->setPageSize(1);
        $collection->setCurPage(1);

        return (int)$collection->getFirstItem()->getId();
    }

    protected function getChangedProductsPrice()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('-1 day');

        $collection = $this->catalogProductCollectionFactory->create();
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToFilter('special_price', ['notnull' => true]);
        $collection->addAttributeToFilter('special_to_date', ['notnull' => true]);
        $collection->addAttributeToFilter('special_to_date', ['lt' => $date->format('Y-m-d H:i:s')]);
        $collection->addFieldToFilter('entity_id', ['gteq' => (int)$this->getLastProcessedProductId()]);
        $collection->setOrder('entity_id', 'asc');
        $collection->getSelect()->limit(1000);

        return $collection->getItems();
    }

    // ---------------------------------------

    protected function getLastProcessedProductId()
    {
        return $this->getHelper('Module')->getRegistry()->getValue(
            '/magento/product/detect_special_price_end_date/last_magento_product_id/'
        );
    }

    protected function setLastProcessedProductId($magentoProductId)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/magento/product/detect_special_price_end_date/last_magento_product_id/',
            (int)$magentoProductId
        );
    }

    //########################################
}
