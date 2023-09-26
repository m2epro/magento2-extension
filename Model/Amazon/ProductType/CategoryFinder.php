<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

use Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Response;

class CategoryFinder
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Processor */
    private $connectProcessor;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $repository;
    /** @var \Ess\M2ePro\Model\MarketplaceFactory */
    private $marketplaceFactory;

    public function __construct(
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder\Repository $repository,
        \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Processor $connectProcessor
    ) {
        $this->marketplaceFactory = $marketplaceFactory;
        $this->repository = $repository;
        $this->connectProcessor = $connectProcessor;
    }

    /**
     * @param int $marketplaceId
     * @param string[] $criteria
     *
     * @return CategoryFinder\Category[]
     */
    public function find(int $marketplaceId, array $criteria): array
    {
        $marketplace = $this->marketplaceFactory->create();
        $marketplace->load($marketplaceId);

        if (!$marketplace->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Invalid marketplace id');
        }

        $request = new \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Request(
            $marketplace->getNativeId(),
            $criteria
        );

        $response = $this->connectProcessor->process($request);
        $availableProductTypes = $this->getAvailableProductTypes($marketplaceId, $response);

        $categories = [];
        foreach ($response->getCategories() as $category) {
            $categoryItem = new CategoryFinder\Category(
                $category['name'],
                $category['isLeaf']
            );

            foreach ($category['nicksOfProductTypes'] as $productTypeNick) {
                if (isset($availableProductTypes[$productTypeNick])) {
                    $productType = new CategoryFinder\ProductType(
                        $availableProductTypes[$productTypeNick]['title'],
                        $availableProductTypes[$productTypeNick]['nick'],
                        $availableProductTypes[$productTypeNick]['templateId']
                    );
                    $categoryItem->addProductType($productType);
                }
            }

            $categories[] = $categoryItem;
        }

        return $categories;
    }

    /**
     * @param int $marketplaceId
     * @param \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Response $response
     *
     * @return array<string, array{nick:string, title:string, templateId: int|null}>
     */
    private function getAvailableProductTypes(int $marketplaceId, Response $response): array
    {
        $nicks = [];
        foreach ($response->getCategories() as $category) {
            foreach ($category['nicksOfProductTypes'] as $productTypeNick) {
                $nicks[] = $productTypeNick;
            }
        }

        return $this->repository->getAvailableProductTypes(
            $marketplaceId,
            array_unique($nicks)
        );
    }
}
