<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

class CategoryService
{
    private \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Processor $getCategoriesConnector;
    private \Ess\M2ePro\Model\Walmart\Dictionary\CategoryFactory $categoryDictionaryFactory;
    private \Ess\M2ePro\Model\Walmart\Dictionary\Category\Repository $categoryDictionaryRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Processor $getCategoriesConnector,
        \Ess\M2ePro\Model\Walmart\Dictionary\CategoryFactory $categoryDictionaryFactory,
        \Ess\M2ePro\Model\Walmart\Dictionary\Category\Repository $categoryDictionaryRepository
    ) {
        $this->getCategoriesConnector = $getCategoriesConnector;
        $this->categoryDictionaryFactory = $categoryDictionaryFactory;
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    public function update(\Ess\M2ePro\Model\Marketplace $marketplace): void
    {
        if (!$marketplace->isComponentModeWalmart()) {
            throw new \LogicException('Marketplace is not Walmart component mode.');
        }

        $this->categoryDictionaryRepository->removeByMarketplace(
            (int)$marketplace->getId()
        );

        $part = $this->processPart(1, $marketplace);
        if ($part->getNextPartNumber() === null) {
            return;
        }

        $totalParts = $part->getTotalParts();
        for ($i = 2; $i <= $totalParts; $i++) {
            $part = $this->processPart($part->getNextPartNumber(), $marketplace);
            if ($part->getNextPartNumber() === null) {
                break;
            }
        }
    }

    private function processPart(
        int $partNumber,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response\Part {
        $response = $this->getCategoriesConnector->process(
            $marketplace,
            $partNumber
        );

        $this->createCategoryDictionaries((int)$marketplace->getId(), $response);

        return $response->getPart();
    }

    private function createCategoryDictionaries(
        int $marketplaceId,
        \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response $response
    ) {
        $categoryDictionaryEntities = [];
        foreach ($response->getCategories() as $responseCategory) {
            $categoryDictionaryEntities[] = $this->getCategoryDictionary(
                $marketplaceId,
                $responseCategory
            );
        }

        $this->categoryDictionaryRepository->bulkCreate($categoryDictionaryEntities);
    }

    private function getCategoryDictionary(
        int $marketplaceId,
        \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response\Category $responseCategory
    ): \Ess\M2ePro\Model\Walmart\Dictionary\Category {
        if ($responseCategory->getParentId() === null) {
            return $this->categoryDictionaryFactory->createAsRoot(
                $marketplaceId,
                $responseCategory->getId(),
                $responseCategory->getTitle()
            );
        }

        if (!$responseCategory->isLeaf()) {
            return $this->categoryDictionaryFactory->createAsChild(
                $marketplaceId,
                $responseCategory->getParentId(),
                $responseCategory->getId(),
                $responseCategory->getTitle()
            );
        }

        $responseProductType = $responseCategory->getProductType();
        return $this->categoryDictionaryFactory->createAsLeaf(
            $marketplaceId,
            $responseCategory->getParentId(),
            $responseCategory->getId(),
            $responseCategory->getTitle(),
            $responseProductType->getNick(),
            $responseProductType->getTitle()
        );
    }
}
