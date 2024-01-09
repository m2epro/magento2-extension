<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Mapping\Tabs;

class AttributeMapping extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'amazon/settings/attribute_mapping.phtml';

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributesHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributesHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->magentoAttributesHelper = $magentoAttributesHelper;
        $this->collectionFactory = $collectionFactory;
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/amazon_mapping_attributeMapping/save'),
            \Ess\M2ePro\Block\Adminhtml\Amazon\Settings\Tabs::TAB_ID_ATTRIBUTE_MAPPING
        );

        return parent::_beforeToHtml();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping[]
     */
    public function getMappedAttributes(): array
    {
        $collection = $this->collectionFactory->create();

        return $collection->getItems();
    }

    public function makeMagentoAttributesDropDownHtml(
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping $attributeMapping
    ): string {
        $attributes = $this->magentoAttributesHelper->getAll();

        $html = sprintf(
            '<select id="attribute-%1$s" name="attributes[%1$s]" class="%2$s">',
            $attributeMapping->getId(),
            'select admin__control-select M2ePro-custom-attribute-can-be-created'
        );
        $html .= sprintf('<option value="">%s</option>', __('None'));
        $html .= sprintf(
            '<optgroup label="%s">',
            __('Magento Attributes')
        );
        foreach ($attributes as $attribute) {
            $html .= sprintf(
                '<option value="%1$s"%3$s>%2$s</option>',
                $attribute['code'],
                $attribute['label'],
                $attribute['code'] === $attributeMapping->getMagentoAttributeCode() ? ' selected' : ''
            );
        }
        $html .= '</optgroup>';
        $html .= '</select>';

        return $html;
    }

    public function getEmptyMappingsText(): string
    {
        return __(
            'Create a Product Type to review default attribute mappings on <a href="%link">this page</a>.',
            [
                'link' => $this->_urlBuilder->getUrl('*/amazon_template_productType/index'),
            ]
        );
    }
}
