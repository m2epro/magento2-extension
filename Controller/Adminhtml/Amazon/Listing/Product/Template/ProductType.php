<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Template;

abstract class ProductType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Magento\Framework\DB\TransactionFactory */
    protected $transactionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeSettingsFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory */
    private $snapshotBuilderFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory */
    private $diffFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory */
    private $changeProcessorFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;

    /**
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $diffFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $changeProcessorFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeSettingsFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $snapshotBuilderFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\ChangeProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeSettingsFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->productTypeSettingsFactory = $productTypeSettingsFactory;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->productTypeResource = $productTypeResource;
        $this->diffFactory = $diffFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->amazonListingProductResource = $amazonListingProductResource;
        parent::__construct($amazonFactory, $context);
    }

    protected function setProductTypeForProducts($productsIds, $productTypeId, $isGeneralIdOwnerWillBeSet = false): void
    {
        if (empty($productsIds)) {
            return;
        }

        $collection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);
        $collection->addFieldToFilter('id', ['in' => $productsIds]);

        if ($collection->getSize() === 0) {
            return;
        }

        $transaction = $this->transactionFactory->create();
        $oldProductTypeIds = [];

        try {
            /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();
                $oldProductTypeIds[$listingProduct->getId()] = $amazonListingProduct->getTemplateProductTypeId();
                $amazonListingProduct->setTemplateProductTypeId($productTypeId);

                $additionalData = $listingProduct->getAdditionalData();
                $variationManager = $amazonListingProduct->getVariationManager();
                if (
                    empty($additionalData['migrated_to_product_types'])
                    && ($isGeneralIdOwnerWillBeSet || $listingProduct->getChildObject()->isGeneralIdOwner())
                    && $variationManager->isVariationProduct()
                    && $variationManager->isVariationParent()
                ) {
                    $backupKeys = ['variation_matched_attributes', 'variation_channel_attributes_sets'];
                    foreach ($backupKeys as $key) {
                        if (
                            empty($additionalData["backup_$key"])
                            && !empty($additionalData[$key])
                        ) {
                            $additionalData["backup_$key"] = $additionalData[$key];
                        }
                    }

                    $additionalData['running_migration_to_product_types'] = true;
                    unset($additionalData['variation_channel_theme']);
                    unset($additionalData['is_variation_channel_theme_set_manually']);
                    unset($additionalData['variation_matched_attributes']);

                    $listingProduct->setSettings('additional_data', $additionalData);
                    $amazonListingProduct->setData('variation_parent_need_processor', 0);
                }

                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            $oldProductTypeIds = [];
        }

        if ($oldProductTypeIds === []) {
            return;
        }

        $newSnapshot = $this->makeProductTypeSnapshot($productTypeId);

        /**@var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $oldProductTypeId = $oldProductTypeIds[$listingProduct->getId()];
            $oldSnapshot = $this->makeProductTypeSnapshot($oldProductTypeId);

            if (empty($newSnapshot) && empty($oldSnapshot)) {
                continue;
            }

            $diff = $this->diffFactory->create();
            $diff->setOldSnapshot($oldSnapshot);
            $diff->setNewSnapshot($newSnapshot);

            $changeProcessor = $this->changeProcessorFactory->create();
            $changeProcessor->process(
                $diff,
                [
                    [
                        'id' => $listingProduct->getId(),
                        'status' => $listingProduct->getStatus(),
                    ],
                ]
            );
        }
    }

    protected function filterLockedProducts($productsIdsParam)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable();

        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $select = $connection->select();
            $select->from(['lo' => $table], ['object_id'])
                   ->where('model_name = "Listing_Product"')
                   ->where('object_id IN (?)', $productsIdsParamChunk)
                   ->where('tag IS NOT NULL');

            $lockedProducts = $connection->fetchCol($select);

            foreach ($lockedProducts as $id) {
                $key = array_search($id, $productsIdsParamChunk);
                if ($key !== false) {
                    unset($productsIdsParamChunk[$key]);
                }
            }

            $productsIds = array_merge($productsIds, $productsIdsParamChunk);
        }

        return $productsIds;
    }

    /**
     * @param array $productsIdsParam
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function filterProductsForAssignOrUnassign(array $productsIdsParam): array
    {
        $productsIds = [];
        $productsIdsParam = array_chunk($productsIdsParam, 1000);
        foreach ($productsIdsParam as $productsIdsParamChunk) {
            $connection = $this->amazonListingProductResource->getConnection();
            $select = $connection->select();

            // selecting all except parents general_id owners or simple general_id owners without general_id
            $select->from($this->amazonListingProductResource->getMainTable(), 'listing_product_id')
                   ->where(
                       'is_general_id_owner = 0
                OR (is_general_id_owner = 1
                    AND is_variation_parent = 0 AND general_id IS NOT NULL)'
                   );

            $select->where('listing_product_id IN (?)', $productsIdsParamChunk);

            $productsIds = array_merge($productsIds, $connection->fetchCol($select));
        }

        return $productsIds;
    }

    protected function runProcessorForParents($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product')
            ->getResource()
            ->getMainTable();

        $select = $connection->select();
        $select->from(['alp' => $tableAmazonListingProduct], ['listing_product_id'])
               ->where('listing_product_id IN (?)', $productsIds)
               ->where('is_variation_parent = ?', 1);

        $productsIds = $connection->fetchCol($select);

        foreach ($productsIds as $productId) {
            $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);
            $listingProduct->getChildObject()->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }

    /**
     * @param $productTypeId
     *
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType
     */
    private function loadProductTypeById($productTypeId): \Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        $object = $this->productTypeSettingsFactory->create();
        $this->productTypeResource->load($object, $productTypeId);

        return $object;
    }

    /**
     * @param $model
     *
     * @return array
     */
    private function makeSnapshot($model): array
    {
        $snapshotBuilder = $this->snapshotBuilderFactory->create();
        $snapshotBuilder->setModel($model);

        return $snapshotBuilder->getSnapshot();
    }

    /**
     * @param $productTypeId
     *
     * @return array
     */
    private function makeProductTypeSnapshot($productTypeId): array
    {
        $model = $this->loadProductTypeById($productTypeId);

        if ($model->getId() === null) {
            return [];
        }

        return $this->makeSnapshot($model);
    }

    //########################################
}
