<?php

declare(strict_types=1);

namespace Ess\M2ePro\Ui\Template\ProductType\Component\Listing\Column;

class Marketplace extends \Magento\Ui\Component\Listing\Columns\Column
{
    private static array $marketplacesRuntime = [];

    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $marketplaceId = (int)$row['marketplace_id'];
            if (!isset(self::$marketplacesRuntime[$marketplaceId])) {
                self::$marketplacesRuntime[$marketplaceId] = $this->amazonMarketplaceRepository->get($marketplaceId);
            }

            $row['marketplace'] = self::$marketplacesRuntime[$marketplaceId]->getTitle();
        }

        return $dataSource;
    }
}
