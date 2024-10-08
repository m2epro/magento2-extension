<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\Listing as ListingModel;
use Ess\M2ePro\Model\Ebay\Listing\Wizard as WizardModel;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Exception\NotFoundException as ListingWizardNotFoundException;
use Ess\M2ePro\Model\Ebay\Listing\WizardFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\CollectionFactory as WizardCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product as WizardProductResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product\CollectionFactory as ProductCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step as StepResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step\CollectionFactory as StepCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard as WizardResource;

class Repository
{
    private WizardResource $wizardResource;
    private StepResource $stepResource;
    private StepCollectionFactory $stepCollectionFactory;
    private WizardFactory $wizardFactory;
    private WizardCollectionFactory $wizardCollectionFactory;
    private WizardProductResource $wizardProductResource;
    private ProductCollectionFactory $productCollectionFactory;
    /**
     * @var WizardProductResource
     */
    private WizardProductResource $productResource;
    private Tables $tablesHelper;

    public function __construct(
        WizardFactory $wizardFactory,
        WizardResource $wizardResource,
        WizardCollectionFactory $wizardCollectionFactory,
        StepResource $stepResource,
        StepCollectionFactory $stepCollectionFactory,
        WizardProductResource $wizardProductResource,
        ProductCollectionFactory $productCollectionFactory,
        WizardProductResource $productResource,
        Tables $tablesHelper
    ) {
        $this->wizardResource = $wizardResource;
        $this->stepResource = $stepResource;
        $this->stepCollectionFactory = $stepCollectionFactory;
        $this->wizardFactory = $wizardFactory;
        $this->wizardCollectionFactory = $wizardCollectionFactory;
        $this->wizardProductResource = $wizardProductResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productResource = $productResource;
        $this->tablesHelper = $tablesHelper;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function create(WizardModel $wizard): void
    {
        $this->wizardResource->save($wizard);
    }

    /**
     * @param array $steps
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createSteps(array $steps): void
    {
        foreach ($steps as $step) {
            $this->stepResource->save($step);
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(WizardModel $wizard): void
    {
        $this->wizardResource->save($wizard);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard\Step $step
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveStep(Step $step): void
    {
        $this->stepResource->save($step);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product $product
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function saveProduct(Product $product): void
    {
        $this->wizardProductResource->save($product);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product $product
     *
     * @return void
     * @throws \Exception
     */
    public function removeProduct(Product $product): void
    {
        $this->wizardProductResource->delete($product);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function remove(WizardModel $wizard): void
    {
        foreach ($wizard->getSteps() as $step) {
            $this->stepResource->delete($step);
        }
        $this->removeAllProducts($wizard);

        $this->wizardResource->delete($wizard);
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard
     */
    public function get(int $id): WizardModel
    {
        $wizard = $this->find($id);
        if ($wizard === null) {
            throw new ListingWizardNotFoundException('Wizard not found.');
        }

        return $wizard;
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard|null
     */
    public function find(int $id): ?WizardModel
    {
        $wizard = $this->wizardFactory->create();
        $this->wizardResource->load($wizard, $id);

        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);

        return $wizard;
    }

    /**
     * @param int $id
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product|null
     */
    public function findProductById(
        int $id,
        WizardModel $wizard
    ): ?Product {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(
                WizardProductResource::COLUMN_WIZARD_ID,
                $wizard->getId(),
            )
            ->addFieldToFilter(WizardProductResource::COLUMN_ID, ['eq' => $id]);

        $product = $productCollection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        $product->initWizard($wizard);

        return $product;
    }

    /**
     * @param string $type
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard|null
     */
    public function findNotCompletedWizardByType(string $type): ?WizardModel
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_IS_COMPLETED, ['eq' => 0])
            ->addFieldToFilter(WizardResource::COLUMN_TYPE, ['eq' => $type]);

        $wizard = $collection->getFirstItem();
        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);

        return $wizard;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing $listing
     * @param string $type
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard|null
     */
    public function findNotCompletedByListingAndType(
        ListingModel $listing,
        string $type
    ): ?WizardModel {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(
                WizardResource::COLUMN_LISTING_ID,
                ['eq' => $listing->getId()],
            )
            ->addFieldToFilter(WizardResource::COLUMN_IS_COMPLETED, ['eq' => 0])
            ->addFieldToFilter(WizardResource::COLUMN_TYPE, ['eq' => $type]);

        $wizard = $collection->getFirstItem();
        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);
        $wizard->initListing($listing);

        return $wizard;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard|null
     */
    public function findNotCompleted(): ?WizardModel
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_IS_COMPLETED, ['eq' => 0]);

        $wizard = $collection->getFirstItem();
        if ($wizard->isObjectNew()) {
            return null;
        }

        $this->loadSteps($wizard);

        return $wizard;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return array
     */
    public function findSteps(WizardModel $wizard): array
    {
        $stepCollection = $this->stepCollectionFactory->create();
        $stepCollection->addFieldToFilter(
            StepResource::COLUMN_WIZARD_ID,
            $wizard->getId(),
        );

        return array_values($stepCollection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return void
     */
    private function loadSteps(WizardModel $wizard): void
    {
        $steps = $this->findSteps($wizard);
        $wizard->initSteps($steps);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return array
     */
    public function findAllProducts(WizardModel $wizard): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter(
            WizardProductResource::COLUMN_WIZARD_ID,
            $wizard->getId(),
        );

        $result = [];
        foreach ($productCollection->getItems() as $product) {
            $product->initWizard($wizard);
            $result[] = $product;
        }

        return $result;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return array
     */
    public function findNotProcessed(WizardModel $wizard, array $ids = []): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId())
            ->addFieldToFilter(WizardProductResource::COLUMN_IS_PROCESSED, 0);

        if (!empty($ids)) {
            $collection->addFieldToFilter(
                WizardProductResource::COLUMN_ID,
                ['in' => $ids]
            );
        }

        $result = [];
        foreach ($collection->getItems() as $product) {
            $product->initWizard($wizard);
            $result[] = $product;
        }

        return $result;
    }

    /**
     * @param int $id
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product|null
     */
    public function findProductByMagentoId(
        int $id,
        WizardModel $wizard
    ): ?Product {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection
            ->addFieldToFilter(WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId())
            ->addFieldToFilter(WizardProductResource::COLUMN_MAGENTO_PRODUCT_ID, ['eq' => $id]);

        $product = $productCollection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        $product->initWizard($wizard);

        return $product;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProcessedProductsCount(WizardModel $wizard): int
    {
        $connection = $this->wizardProductResource->getConnection();
        $tableName = $this->wizardProductResource->getMainTable();

        $select = $connection->select()
                             ->from($tableName, ['COUNT(*)'])
                             ->where(WizardProductResource::COLUMN_WIZARD_ID . ' = ?', $wizard->getId())
                             ->where(WizardProductResource::COLUMN_IS_PROCESSED . ' = ?', 1);

        return (int)$connection->fetchOne($select);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeAllProducts(WizardModel $wizard): void
    {
        $this->wizardProductResource->getConnection()->delete(
            $this->wizardProductResource->getMainTable(),
            [WizardProductResource::COLUMN_WIZARD_ID . ' = ?' => $wizard->getId()],
        );
    }

    public function removeAllProductsValidationErrors(WizardModel $wizard): void
    {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_VALIDATION_STATUS => 0,
                    WizardProductResource::COLUMN_VALIDATION_ERRORS => '',
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId()),
                ],
            );
    }

    public function resetCategories(array $ids): void
    {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_TEMPLATE_CATEGORY_ID => null,
                    WizardProductResource::COLUMN_TEMPLATE_CATEGORY_SECONDARY_ID => null,
                    WizardProductResource::COLUMN_STORE_CATEGORY_ID => null,
                    WizardProductResource::COLUMN_STORE_CATEGORY_SECONDARY_ID => null,
                ],
                [WizardProductResource::COLUMN_ID . ' IN (?)' => $ids],
            );
    }

    /**
     * @param \DateTime $borderDate
     *
     * @return WizardModel[]
     */
    public function findOldCompleted(\DateTime $borderDate): array
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_IS_COMPLETED, ['eq' => 1])
            ->addFieldToFilter(WizardResource::COLUMN_PROCESS_END_DATE, ['lt' => $borderDate->format('Y-m-d H:i:s')]);

        return array_values($collection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing $listing
     *
     * @return array
     */
    public function findWizardsByListing(ListingModel $listing): array
    {
        $collection = $this->wizardCollectionFactory->create();
        $collection
            ->addFieldToFilter(WizardResource::COLUMN_LISTING_ID, ['eq' => $listing->getId()]);

        return array_values($collection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard $wizard
     * @param array $wizardProductsIds
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function markProductsAsCompleted(
        WizardModel $wizard,
        array $wizardProductsIds
    ): void {
        $this->wizardProductResource
            ->getConnection()
            ->update(
                $this->wizardProductResource->getMainTable(),
                [
                    WizardProductResource::COLUMN_IS_PROCESSED => 1,
                ],
                [
                    sprintf('%s = %d', WizardProductResource::COLUMN_WIZARD_ID, $wizard->getId()),
                    sprintf('%s IN (%s)', WizardProductResource::COLUMN_ID, implode(',', $wizardProductsIds)),
                ],
            );
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product[] $wizardProducts
     *
     * @return void
     */
    public function addOrUpdateProducts(array $wizardProducts): void
    {
        if (empty($wizardProducts)) {
            return;
        }

        $tableName = $this->wizardProductResource->getMainTable();
        $connection = $this->wizardProductResource->getConnection();

        foreach (array_chunk($wizardProducts, 500) as $productsChunk) {
            $preparedData = [];
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product $product */
            foreach ($productsChunk as $product) {
                $preparedData[] = [
                    'wizard_id' => $product->getWizardId(),
                    'unmanaged_product_id' => $product->getUnmanagedProductId(),
                    'magento_product_id' => $product->getMagentoProductId(),
                    'template_category_id' => $product->getTemplateCategoryId(),
                    'is_processed' => (int)$product->isProcessed(),
                ];
            }

            $connection->insertOnDuplicate($tableName, $preparedData, ['template_category_id', 'is_processed']);
        }
    }
}
