<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

class SetRepricerTemplateId
{
    private array $cachedRepricerTemplates = [];

    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Magento\Framework\DB\TransactionFactory $transactionFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\SnapshotBuilderFactory $snapshotBuilderFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $repricerTemplateRepository;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\DiffFactory $diffFactory;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\Repository $repricerTemplateRepository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\DiffFactory $diffFactory,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\ChangeProcessorFactory $changeProcessorFactory
    ) {
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->repricerTemplateRepository = $repricerTemplateRepository;
        $this->diffFactory = $diffFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
    }

    public function execute(array $listingProductIds, ?int $repricerTemplateId)
    {
        if (empty($listingProductIds)) {
            return;
        }

        $collection = $this->listingProductCollectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter('id', ['in' => $listingProductIds]);

        if ($collection->getSize() == 0) {
            return;
        }

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();

        $updatedTemplates = [];
        try {
            foreach ($collection->getItems() as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();

                $updatedTemplates[] = [
                    'old' => $walmartListingProduct->getTemplateRepricerId(),
                    'new' => $repricerTemplateId,
                    'listing_product' => $listingProduct,
                ];

                $walmartListingProduct->setTemplateRepricerId($repricerTemplateId);
                $transaction->addObject($listingProduct);
            }

            $transaction->save();
        } catch (\Exception $e) {
            return;
        }

        $this->processTemplateChanges($updatedTemplates);
        $this->runProcessorForParents($updatedTemplates);
    }

    private function processTemplateChanges(array $updatedTemplates): void
    {
        foreach ($updatedTemplates as $updatedTemplate) {
            $oldRepricerTemplateId = $updatedTemplate['old'];
            $newRepricerTemplateId = $updatedTemplate['new'];
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $updatedTemplate['listing_product'];

            $diff = $this->diffFactory->create();
            $diff->setOldSnapshot($this->createSnapshot($oldRepricerTemplateId));
            $diff->setNewSnapshot($this->createSnapshot($newRepricerTemplateId));

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

    private function createSnapshot(?int $templateId): array
    {
        if ($templateId === null) {
            return [];
        }

        $snapshotBuilder = $this->snapshotBuilderFactory->create();
        $snapshotBuilder->setModel($this->getRepricerTemplateById($templateId));

        return $snapshotBuilder->getSnapshot();
    }

    private function getRepricerTemplateById(int $templateId): \Ess\M2ePro\Model\Walmart\Template\Repricer
    {
        if (isset($this->cachedRepricerTemplates[$templateId])) {
            return $this->cachedRepricerTemplates[$templateId];
        }

        $this->cachedRepricerTemplates[$templateId] = $this->repricerTemplateRepository->get($templateId);

        return $this->cachedRepricerTemplates[$templateId];
    }

    private function runProcessorForParents(array $updatedTemplates): void
    {
        foreach ($updatedTemplates as $updatedTemplate) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $updatedTemplate['listing_product'];
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if (!$walmartListingProduct->getData('is_variation_parent')) {
                continue;
            }

            $walmartListingProduct
                ->getVariationManager()
                ->getTypeModel()
                ->getProcessor()
                ->process();
        }
    }
}
