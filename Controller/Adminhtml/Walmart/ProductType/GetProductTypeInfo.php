<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class GetProductTypeInfo extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\Dictionary\ProductTypeService $dictionaryProductTypeService;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductTypeService $dictionaryProductTypeCreateService,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dictionaryProductTypeService = $dictionaryProductTypeCreateService;
        $this->productTypeRepository = $productTypeRepository;
        $this->marketplaceRepository = $marketplaceRepository;
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

        $productTypeNick = $this->getRequest()->getParam('product_type');
        if ($productTypeNick === null) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct product_type.',
            ]);

            return $this->getResult();
        }

        $marketplace = $this->marketplaceRepository->get((int)$marketplaceId);
        $contentData = $this->buildContentData($productTypeNick, $marketplace);

        $this->setJsonContent([
            'result' => true,
            'data' => $contentData,
        ]);

        return $this->getResult();
    }

    private function buildContentData(string $productTypeNick, \Ess\M2ePro\Model\Marketplace $marketplace): array
    {
        $productTypeDictionary = $this->dictionaryProductTypeService->retrieve(
            $productTypeNick,
            $marketplace
        );

        $productType = $this->productTypeRepository->findByDictionary($productTypeDictionary);
        if ($productType === null) {
            return $this->getContentData(
                $productTypeDictionary->getAttributes()
            );
        }

        return $this->getContentData(
            $productTypeDictionary->getAttributes(),
            $productType->getRawAttributesSettings()
        );
    }

    private function getContentData(
        array $dictionaryProductTypeAttributes,
        array $productTypeAttributesSettings = []
    ): array {
        return [
            'groups' => [
                ['title' => (string)__('Attributes'), 'nick' => 'attributes',],
            ],
            'scheme' => $dictionaryProductTypeAttributes,
            'settings' => $productTypeAttributesSettings,
            'timezone_shift' => $this->getTimezoneShift(),
            'specifics_default_settings' => [],
        ];
    }

    private function getTimezoneShift(): int
    {
        $dateLocal = \Ess\M2ePro\Helper\Date::createDateInCurrentZone('2024-01-01');
        $dateUTC = \Ess\M2ePro\Helper\Date::createDateGmt($dateLocal->format('Y-m-d H:i:s'));

        return $dateUTC->getTimestamp() - $dateLocal->getTimestamp();
    }
}
