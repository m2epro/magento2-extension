<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\AttributeMapping;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Manager $bundleMappingService;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler $bundleChangeHandler;
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService;
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedService;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler $bundleChangeHandler,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Manager $bundleMappingService,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService,
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->bundleMappingService = $bundleMappingService;
        $this->bundleChangeHandler = $bundleChangeHandler;
        $this->gpsrService = $gpsrService;
        $this->groupedService = $groupedService;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        // ----------------------------------------

        if (!empty($post['mapping'])) {
            $this->processBundleAttributes($post['mapping']);
        }

        // ----------------------------------------

        $wasChangedGpsr = false;
        if (!empty($post['gpsr_attributes'])) {
            $wasChangedGpsr = $this->processGpsrAttributes($post['gpsr_attributes']);
        }

        // ----------------------------------------

        if (!empty($post['grouped_attributes'])) {
            $this->processGroupedAttributes($post['grouped_attributes']);
        }

        // ----------------------------------------

        $this->setJsonContent(
            [
                'success' => true,
                'was_changed_gpsr' => $wasChangedGpsr,
            ]
        );

        return $this->getResult();
    }

    private function processBundleAttributes(array $mappingAttributes): void
    {
        $changedTitles = [];
        foreach ($mappingAttributes as $base64EncodedTitle => $attributeCode) {
            $title = base64_decode($base64EncodedTitle);
            $mapping = $this->bundleMappingService->save($title, $attributeCode);

            if ($mapping !== null) {
                $changedTitles[] = $mapping->getTitle();
            }
        }

        $this->bundleChangeHandler->handle($changedTitles);
    }

    private function processGpsrAttributes(array $gpsrAttributes): bool
    {
        $attributes = [];
        foreach ($gpsrAttributes as $channelCode => $attributeData) {
            if (
                (int)$attributeData['mode'] === \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_NONE
                || empty($attributeData['value'])
            ) {
                continue;
            }

            $attributes[] = new \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair(
                null,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE,
                (int)$attributeData['mode'],
                \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Provider::getAttributeTitle($channelCode) ?? $channelCode,
                $channelCode,
                $attributeData['value']
            );
        }

        return $this->gpsrService->save($attributes) > 0;
    }

    private function processGroupedAttributes(array $groupedProductAttributes): void
    {
        $attributes = [];
        foreach ($groupedProductAttributes as $channelCode => $magentoCode) {
            if (empty($magentoCode)) {
                continue;
            }

            $attributes[] = new \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair(
                null,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService::MAPPING_TYPE,
                \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\Grouped\Provider::getAttributeTitle($channelCode)
                    ?? $channelCode,
                $channelCode,
                $magentoCode
            );
        }

        $this->groupedService->save($attributes);
    }
}
