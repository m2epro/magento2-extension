<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Settings\AttributeMapping;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Settings
{
    private \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService $variationAttributesService;
    private \Ess\M2ePro\Model\AttributeOptionMapping\PairFactory $pairFactory;

    public function __construct(
        \Ess\M2ePro\Model\AttributeOptionMapping\PairFactory $pairFactory,
        \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService $variationAttributesService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->variationAttributesService = $variationAttributesService;
        $this->pairFactory = $pairFactory;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        $wasChangedVariationAttributes = false;
        if (!empty($post['variation_attributes'])) {
            $wasChangedVariationAttributes = $this->processVariationsAttributes($post['variation_attributes']);
        }

        $this->setJsonContent(
            [
                'success' => true,
                'was_changed_gpsr' => $wasChangedVariationAttributes,
            ]
        );

        return $this->getResult();
    }

    private function processVariationsAttributes(array $variationAttributes): bool
    {
        $mappings = [];
        foreach ($variationAttributes as $productTypeId => $productTypeAttributes) {
            foreach ($productTypeAttributes as $channelAttributeName => $channelAttributeOptions) {
                foreach ($channelAttributeOptions as $channelOptionName => $magentoAttributeData) {
                    foreach ($magentoAttributeData as $magentoAttributeCode => $magentoAttributeOptionId) {
                        if (empty($magentoAttributeOptionId)) {
                            continue;
                        }

                        $channelAttributeTitle = $this->variationAttributesService
                            ->getChannelAttributeTitle($productTypeId, $channelAttributeName);
                        $chanelOptionTitle = $this->variationAttributesService
                            ->getChannelOptionTitle($productTypeId, $channelAttributeName, $channelOptionName);
                        $magentoOptionTitle = $this->variationAttributesService
                            ->getMagentoOptionTitle($magentoAttributeCode, (int)$magentoAttributeOptionId);
                        $pair = $this->pairFactory->create(
                            \Ess\M2ePro\Helper\Component\Walmart::NICK,
                            \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService::MAPPING_TYPE,
                            (int)$productTypeId,
                            $channelAttributeTitle,
                            $channelAttributeName,
                            $chanelOptionTitle,
                            $channelOptionName,
                            $magentoAttributeCode,
                            (int)$magentoAttributeOptionId,
                            $magentoOptionTitle
                        );

                        $mappings[] = $pair;
                    }
                }
            }
        }

        return $this->variationAttributesService->save($mappings) > 0;
    }
}
