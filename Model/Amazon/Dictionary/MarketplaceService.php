<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class MarketplaceService
{
    private \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory;
    private \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceFactory $dictionaryMarketplaceFactory;
    private \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceFactory $dictionaryMarketplaceFactory,
        \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository
    ) {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->dictionaryMarketplaceFactory = $dictionaryMarketplaceFactory;
        $this->dictionaryMarketplaceRepository = $dictionaryMarketplaceRepository;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    public function isExistForMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace): bool
    {
        return $this->dictionaryMarketplaceRepository->findByMarketplace($marketplace) !== null;
    }

    public function update(\Ess\M2ePro\Model\Marketplace $marketplace): \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace
    {
        $dispatcher = $this->dispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Marketplace\Get\InfoWithDetails $command */
        $command = $dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Amazon\Connector\Marketplace\Get\InfoWithDetails::class,
            ['marketplace_id' => $marketplace->getNativeId()]
        );

        $dispatcher->process($command);

        $response = $command->getResponseData();

        [$dictionary, $listProductTypesNicks] = $this->makeDictionary($marketplace, $response['info']);
        $this->processRemovedProductTypes($marketplace, $listProductTypesNicks);
        $this->restoreInvalidProductTypes($marketplace, $listProductTypesNicks);

        return $dictionary;
    }

    private function makeDictionary(\Ess\M2ePro\Model\Marketplace $marketplace, array $info): array
    {
        [$preparedProductTypes, $listProductTypesNicks] = $this->collectProductTypes($info['details']['product_type']);

        $dictionary = $this->dictionaryMarketplaceFactory->create(
            $marketplace,
            $preparedProductTypes,
        );

        $this->dictionaryMarketplaceRepository->removeByMarketplace($marketplace);
        $this->dictionaryMarketplaceRepository->create($dictionary);

        return [$dictionary, $listProductTypesNicks];
    }

    private function collectProductTypes(array $productTypeList): array
    {
        $prepared = [];
        $list = [];
        foreach ($productTypeList as $row) {
            $prepared[] = [
                'nick' => $row['nick'],
                'title' => $row['title'],
            ];

            $list[] = $row['nick'];
        }

        return [$prepared, $list];
    }

    private function processRemovedProductTypes(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $listProductTypesNicks
    ): void {
        $existProductTypesMap = array_flip($listProductTypesNicks);
        foreach ($this->dictionaryProductTypeRepository->findByMarketplace($marketplace) as $productType) {
            if (isset($existProductTypesMap[$productType->getNick()])) {
                continue;
            }

            $templates = $this->templateProductTypeRepository->findByDictionary($productType);
            if (empty($templates)) {
                $this->dictionaryProductTypeRepository->remove($productType);

                continue;
            }

            $productType->markAsInvalid();

            $this->dictionaryProductTypeRepository->save($productType);
        }
    }

    private function restoreInvalidProductTypes(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        mixed $listProductTypesNicks
    ): void {
        $existProductTypesMap = array_flip($listProductTypesNicks);
        foreach ($this->dictionaryProductTypeRepository->findByMarketplace($marketplace) as $productType) {
            if (!$productType->isInvalid()) {
                continue;
            }

            if (!isset($existProductTypesMap[$productType->getNick()])) {
                continue;
            }

            $productType->markAsValid();

            $this->dictionaryProductTypeRepository->save($productType);
        }
    }
}
