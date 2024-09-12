<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class ProductTypeService
{
    private \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;
    /**
     * @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeFactory
     */
    private ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache $issueOutOfDateCache;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory,
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\Amazon\Marketplace\Issue\ProductTypeOutOfDate\Cache $issueOutOfDateCache
    ) {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->productTypeFactory = $productTypeFactory;
        $this->issueOutOfDateCache = $issueOutOfDateCache;
    }

    public function retrieve(
        string $nick,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType {
        if (!$marketplace->isComponentModeAmazon()) {
            throw new \LogicException('Marketplace is not Amazon component mode.');
        }

        $productType = $this->dictionaryProductTypeRepository->findByMarketplaceAndNick(
            (int)$marketplace->getId(),
            $nick
        );
        if ($productType !== null) {
            return $productType;
        }

        $data = $this->getData($nick, $marketplace);

        $productType = $this->productTypeFactory->create(
            $marketplace,
            $nick,
            $data['title'],
            $data['attributes'],
            $data['variation_themes'],
            $data['attributes_groups'],
            \Ess\M2ePro\Helper\Date::createDateGmt($data['last_update']),
            \Ess\M2ePro\Helper\Date::createCurrentGmt(),
        );

        $this->dictionaryProductTypeRepository->create($productType);

        return $productType;
    }

    public function update(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType): void
    {
        $marketplace = $this->amazonMarketplaceRepository->get($productType->getMarketplaceId());

        $data = $this->getData($productType->getNick(), $marketplace);

        $productType->setVariationThemes($data['variation_themes'])
                    ->setScheme($data['attributes'])
                    ->setAttributesGroups($data['attributes_groups'])
                    ->setServerDetailsLastUpdateDate(\Ess\M2ePro\Helper\Date::createDateGmt($data['last_update']))
                    ->setClientDetailsLastUpdateDate(\Ess\M2ePro\Helper\Date::createCurrentGmt());

        $this->dictionaryProductTypeRepository->save($productType);

        $this->clearCache();
    }

    private function getData(string $nick, \Ess\M2ePro\Model\Marketplace $marketplace): array
    {
        $dispatcher = $this->dispatcherFactory->create();

        /** @var \Ess\M2ePro\Model\Amazon\Connector\ProductType\Get\Info $command */
        $command = $dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Amazon\Connector\ProductType\Get\Info::class,
            [
                'product_type_nick' => $nick,
                'marketplace_id' => $marketplace->getNativeId(),
            ]
        );

        $dispatcher->process($command);

        return $command->getResponseData();
    }

    private function clearCache(): void
    {
        $this->issueOutOfDateCache->clear();
    }
}
