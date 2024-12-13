<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs\AttributeMapping;

class VariationAttributesFieldsetFill
{
    private \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider $formDataLoader;
    private \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService $variationAttributesService;
    private \Magento\Backend\Model\UrlInterface $urlBuilder;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService $variationAttributesService,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    ) {
        $this->variationAttributesService = $variationAttributesService;
        $this->urlBuilder = $urlBuilder;
    }

    public function fill(\Magento\Framework\Data\Form\Element\Fieldset $fieldset): void
    {
        $productTypes = $this->variationAttributesService->getAll();

        if (count($productTypes) === 0) {
            $noProductTypeMessage = __(
                'The settings for mapping variation attributes and their ' .
                'options will become available after you assign at least one ' .
                '<a href="%link" target="_blank">Walmart Product Type</a> ' .
                'to your variational products in M2E Pro Listings.',
                [
                    'link' => $this->urlBuilder->getUrl('*/walmart_productType/index')
                ]
            );

            $fieldset->addField(
                'empty_variation_attributes_field',
                \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm::MESSAGES,
                [
                    'messages' => [
                        [
                            'type' => \Magento\Framework\Message\MessageInterface::TYPE_NOTICE,
                            'content' => $noProductTypeMessage,
                        ],
                    ],
                ]
            );

            return;
        }

        foreach ($productTypes as $productType) {
            $productTypeFieldset = $fieldset->addFieldset(
                $this->makeElementId('product-type', [$productType->getId()]),
                [
                    'legend' => $productType->getTitle(),
                    'collapsable' => true,
                ]
            );

            foreach ($productType->getVariationAttributes() as $variationAttribute) {
                $channelAttributeName = sprintf(
                    '<div class="channel-attribute">%s: %s</div>',
                    __('Walmart Attribute'),
                    $variationAttribute->getChannelAttributeTitle()
                );
                $magentoAttributeName = sprintf(
                    '<div class="magento-attribute">%s: %s</div>',
                    __('Magento Attribute'),
                    $variationAttribute->getMagentoAttributeTitle()
                );
                $legend = sprintf(
                    '<div class="attribute-wrapper">%s%s</div>',
                    $channelAttributeName,
                    $magentoAttributeName
                );

                $attributeFieldset = $productTypeFieldset->addFieldset(
                    $this->makeElementId('product-type-attribute', [
                        $productType->getId(),
                        $variationAttribute->getChannelAttributeName(),
                    ]),
                    [
                        'legend' => $legend,
                        'collapsable' => false,
                    ]
                );

                foreach ($variationAttribute->getChannelAttributeOptions() as $attributeOption) {
                    $config = [
                        'label' => $attributeOption->getTitle(),
                        'title' => $attributeOption->getTitle(),
                        'name' => sprintf(
                            'variation_attributes[%s][%s][%s][%s]',
                            $productType->getId(),
                            $variationAttribute->getChannelAttributeName(),
                            $attributeOption->getName(),
                            $variationAttribute->getMagentoAttributeCode()
                        ),
                        'values' => [
                            '' => __('Use Magento Value'),
                            [
                                'label' => __('Magento Attribute Option'),
                                'value' => $variationAttribute->getMagentoAttributeOptions(),
                            ],
                        ],
                        'value' => $attributeOption->getSelectedMagentoOptionId(),
                    ];

                    $attributeFieldset->addField(
                        $this->makeElementId('product-type-attribute-option', [
                            $productType->getId(),
                            $variationAttribute->getChannelAttributeName(),
                            $attributeOption->getName(),
                        ]),
                        'select',
                        $config
                    );
                }
            }
        }
    }

    private function makeElementId(string $prefix, array $idParts): string
    {
        return $prefix . '_' . implode('_', $idParts);
    }
}
