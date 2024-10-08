<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

class MarketplaceService
{
    private \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetInfoWithDetails\Processor $getInfoWithDetailsConnector;
    private \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceFactory $dictionaryMarketplaceFactory;
    private \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository;
    private ProductType\Repository $dictionaryProductTypeRepository;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetInfoWithDetails\Processor $getInfoWithDetailsConnector,
        \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceFactory $dictionaryMarketplaceFactory,
        \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository,
        ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository
    ) {
        $this->getInfoWithDetailsConnector = $getInfoWithDetailsConnector;
        $this->dictionaryMarketplaceFactory = $dictionaryMarketplaceFactory;
        $this->dictionaryMarketplaceRepository = $dictionaryMarketplaceRepository;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function update(\Ess\M2ePro\Model\Marketplace $marketplace): void
    {
        if (!$marketplace->isComponentModeWalmart()) {
            throw new \LogicException('Marketplace is not Walmart component mode.');
        }

        $response = $this->getInfoWithDetailsConnector->process($marketplace);
        $this->dictionaryMarketplaceRepository->removeByMarketplace(
            (int)$marketplace->getId()
        );

        $marketplaceDictionary = $this->dictionaryMarketplaceFactory->createWithProductTypes(
            (int)$marketplace->getId(),
            $response->getProductTypes(),
            $response->getLastUpdate(),
            $response->getLastUpdate()
        );

        $this->dictionaryMarketplaceRepository->create($marketplaceDictionary);

        $this->processRemovedProductTypes(
            $marketplace,
            $response->getProductTypesNicks()
        );

        $this->restoreInvalidProductTypes(
            $marketplace,
            $response->getProductTypesNicks()
        );
    }

    private function processRemovedProductTypes(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $productTypesNicks
    ): void {
        $dictionaryProductTypes = $this->dictionaryProductTypeRepository->retrieveByMarketplace($marketplace);
        foreach ($dictionaryProductTypes as $dictionaryProductType) {
            if (in_array($dictionaryProductType->getNick(), $productTypesNicks)) {
                continue;
            }

            $template = $this->productTypeRepository->findByDictionary($dictionaryProductType);
            if ($template === null) {
                $this->dictionaryProductTypeRepository->remove($dictionaryProductType);

                continue;
            }

            $dictionaryProductType->markAsInvalid();

            $this->dictionaryProductTypeRepository->save($dictionaryProductType);
        }
    }

    private function restoreInvalidProductTypes(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $productTypesNicks
    ): void {
        $dictionaryProductTypes = $this->dictionaryProductTypeRepository->retrieveByMarketplace($marketplace);
        foreach ($dictionaryProductTypes as $productType) {
            if (!$productType->isInvalid()) {
                continue;
            }

            if (!in_array($productType->getNick(), $productTypesNicks)) {
                continue;
            }

            $productType->markAsValid();
            $this->dictionaryProductTypeRepository->save($productType);
        }
    }
}
