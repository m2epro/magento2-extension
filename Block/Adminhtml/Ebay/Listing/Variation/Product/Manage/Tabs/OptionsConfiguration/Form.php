<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs\OptionsConfiguration;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Model\Listing\Product $listingProduct;
    /**
     * @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader\Variation[]
     */
    public array $variationOptions = [];
    private string $formId;

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader\Variation[] $variationOptions
     */
    public function __construct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $variationOptions,
        string $formId,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $data
        );
        $this->listingProduct = $listingProduct;
        $this->variationOptions = $variationOptions;
        $this->formId = $formId;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => $this->formId,
                'action' => $this->getUrl('*/ebay_listing_variation_product_manage/saveOptionsMap', [
                    'listing_product_id' => $this->listingProduct->getId(),
                ]),
            ],
        ]);

        foreach ($this->variationOptions as $variation) {
            $fieldsetId = 'variation-' . $this->filterManager->translitUrl($variation->attribute);
            $fieldset = $form->addFieldset(
                $fieldsetId,
                [
                    'legend' => $variation->attribute,
                    'collapsable' => true,
                ]
            );

            foreach ($variation->options as $option) {
                $fieldId = 'option_' . $this->filterManager
                        ->translitUrl($variation->attribute . '-' . $option->option);

                $defaultOption = [
                    ['value' => '', 'label' => __('Not Set')]
                ];
                $availableOptions = array_map(
                    fn($item) => ['value' => $item, 'label' => $item],
                    $variation->availableOptions
                );

                $fieldset->addField(
                    $fieldId,
                    'select',
                    [
                        'label' => $option->option,
                        'class' => 'M2ePro-validate-mapping-option',
                        'name' => sprintf('options[%s][%s]', $variation->attribute, $option->option),
                        'value' => $option->isReplaced ? $option->channelOption : '',
                        'values' => array_merge($defaultOption, $availableOptions),
                    ]
                );
            }
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
