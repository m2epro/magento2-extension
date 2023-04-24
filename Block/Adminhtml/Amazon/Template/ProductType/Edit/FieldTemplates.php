<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class FieldTemplates extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'amazon/template/product_type/field_templates.phtml';
    /** @var array */
    private $attributes;

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

        // get all attributes except multiselect
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
                'weight'
            ]
        );
    }
    /**
     * @return array
     */
    public function getAvailableAttributes(): array
    {
        return $this->attributes;
    }
}
