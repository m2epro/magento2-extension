<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class SearchProductTypePopup extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    private \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplace;
    private \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;
    private \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $dictionaryMarketplaceService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $dictionaryMarketplaceService,
        \Ess\M2ePro\Model\Amazon\Marketplace\Repository $amazonMarketplaceRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplace,
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dictionaryMarketplace = $dictionaryMarketplace;
        $this->amazonMarketplaceRepository = $amazonMarketplaceRepository;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
        $this->dictionaryMarketplaceService = $dictionaryMarketplaceService;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if ($marketplaceId === null) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct marketplace_id.',
            ]);

            return $this->getResult();
        }

        $productTypes = $this->getAvailableProductTypes($this->amazonMarketplaceRepository->get((int)$marketplaceId));

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General\SearchPopup $block */
        $block = $this->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\General\SearchPopup::class
            );
        $block->setProductTypes($productTypes);

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    private function getAvailableProductTypes(\Ess\M2ePro\Model\Marketplace $marketplace): array
    {
        $marketplaceDictionaryItem = $this->dictionaryMarketplace->findByMarketplace($marketplace);
        if ($marketplaceDictionaryItem === null) {
            $marketplaceDictionaryItem = $this->dictionaryMarketplaceService->update($marketplace);
        }

        $productTypes = $marketplaceDictionaryItem->getProductTypes();
        if (empty($productTypes)) {
            return [];
        }

        $result = [];
        $alreadyUsedProductTypes = [];
        foreach ($this->templateProductTypeRepository->findByMarketplaceId((int)$marketplace->getId()) as $template) {
            $alreadyUsedProductTypes[$template->getDictionary()->getNick()] = (int)$template->getId();
        }

        foreach ($productTypes as $productType) {
            $productTypeData = [
                'nick' => $productType['nick'],
                'title' => $productType['title'],
            ];

            if (isset($alreadyUsedProductTypes[$productType['nick']])) {
                $productTypeData['exist_product_type_id'] = $alreadyUsedProductTypes[$productType['nick']];
            }
            $result[] = $productTypeData;
        }

        return $result;
    }
}
