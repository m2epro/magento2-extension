<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit;

class FieldTemplates extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'walmart/product_type/field_templates.phtml';
    private array $attributes;

    /**
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributes = $magentoAttributeHelper->filterAllAttrByInputTypes(
            [
                'boolean',
                'date',
                'gallery',
                'hidden',
                'image',
                'media_image',
                'multiline',
                'price',
                'select',
                'text',
                'textarea',
                'weight',
                'multiselect',
            ]
        );
    }

    public function getAvailableAttributes(): array
    {
        return $this->attributes;
    }
}
