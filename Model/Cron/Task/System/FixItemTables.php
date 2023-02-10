<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System;

class FixItemTables extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'system/fix_item_tables';

    /** @var int (in seconds) */
    protected $interval = 10 * 60;

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Item */
    private $amazonItemResource;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\LinkingFactory */
    private $amazonLinkingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Item */
    private $walmartItemResource;
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\LinkingFactory */
    private $walmartLinkingFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Item $amazonItemResource,
        \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\LinkingFactory $amazonLinkingFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Item $walmartItemResource,
        \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\LinkingFactory $walmartLinkingFactory
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
        $this->moduleConfiguration = $moduleConfiguration;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->listingResource = $listingResource;
        $this->amazonItemResource = $amazonItemResource;
        $this->amazonLinkingFactory = $amazonLinkingFactory;
        $this->walmartItemResource = $walmartItemResource;
        $this->walmartLinkingFactory = $walmartLinkingFactory;
    }

    //########################################

    /**
     * @return true
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function performActions()
    {
        $this->fixAmazonItemTable();
        $this->fixWalmartItemTable();

        return true;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function fixAmazonItemTable()
    {
        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);
        $listingProductCollection->addFieldToFilter(
            'status',
            ['neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->listingResource->getMainTable()],
            'main_table.listing_id = l.id',
            []
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['ai' => $this->amazonItemResource->getMainTable()],
            <<<CONDITION
second_table.sku = ai.sku
AND l.account_id = ai.account_id
AND l.marketplace_id = ai.marketplace_id
CONDITION
            ,
            []
        );
        $listingProductCollection->addFieldToFilter('ai.sku', ['null' => true]);
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => true]);

        $linkingObject = $this->amazonLinkingFactory->create();

        $this->getOperationHistory()->addText("Bad amazon products: " . $listingProductCollection->count());
        $this->getOperationHistory()->addTimePoint(__CLASS__ . '::' . __METHOD__, 'Fix Amazon Items');

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($listingProductCollection->getItems() as $listingProduct) {
            if (
                $this->moduleConfiguration->isGroupedProductModeSet()
                && $listingProduct->getMagentoProduct()->isGroupedType()
            ) {
                $listingProduct->setSetting('additional_data', 'grouped_product_mode', 1);
                $listingProduct->save();
            }

            $linkingObject->setListingProduct($listingProduct);
            $linkingObject->createAmazonItem();
        }
        $this->getOperationHistory()->saveTimePoint(__CLASS__ . '::' . __METHOD__);
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function fixWalmartItemTable()
    {
        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
        ]);
        $listingProductCollection->addFieldToFilter(
            'status',
            ['neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->listingResource->getMainTable()],
            'main_table.listing_id = l.id',
            []
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['wi' => $this->walmartItemResource->getMainTable()],
            <<<CONDITION
second_table.sku = wi.sku
AND l.account_id = wi.account_id
AND l.marketplace_id = wi.marketplace_id
CONDITION
            ,
            []
        );
        $listingProductCollection->addFieldToFilter('wi.sku', ['null' => true]);
        $listingProductCollection->addFieldToFilter('second_table.sku', ['notnull' => false]);

        $linkingObject = $this->walmartLinkingFactory->create();

        $this->getOperationHistory()->addText("Bad walmart products: " . $listingProductCollection->count());
        $this->getOperationHistory()->addTimePoint(__CLASS__ . '::' . __METHOD__, 'Fix Walmart Items');

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($listingProductCollection->getItems() as $listingProduct) {
            if (
                $this->moduleConfiguration->isGroupedProductModeSet()
                && $listingProduct->getMagentoProduct()->isGroupedType()
            ) {
                $listingProduct->setSetting('additional_data', 'grouped_product_mode', 1);
                $listingProduct->save();
            }

            $linkingObject->setListingProduct($listingProduct);
            $linkingObject->createWalmartItem();
        }
        $this->getOperationHistory()->saveTimePoint(__CLASS__ . '::' . __METHOD__);
    }
}
