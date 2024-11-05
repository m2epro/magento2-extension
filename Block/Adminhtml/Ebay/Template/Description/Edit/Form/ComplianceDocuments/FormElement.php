<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form\ComplianceDocuments;

class FormElement extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    protected function getAttributes()
    {
        return $this->getData('attributes');
    }

    public function getSavedComplianceDocuments()
    {
        $documents = $this->getData('saved_compliance_documents');
        if (empty($documents)) {
            return [
                ['document_type' => '', 'document_attribute' => ''],
            ];
        }

        return $documents;
    }

    public function renderTypesDropdown(int $index, string $selectedType)
    {
        $values = [
            ['value' => '', 'label' => __('None')],
        ];

        $typeNames = \Ess\M2ePro\Model\Ebay\ComplianceDocuments::getDocumentTypeNames();
        foreach ($typeNames as $type => $name) {
            $values[] = ['value' => $type, 'label' => $name];
        }

        foreach ($values as &$value) {
            if ($value['value'] === $selectedType) {
                $value['attrs']['selected'] = 'selected';
            }
        }

        $select = $this->_factoryElement->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_type]",
                    'values' => $values,
                ],
            ]
        );

        $select->setId('document-type-' . $index);
        $select->setForm($this->getForm());

        return $select->toHtml();
    }

    public function renderAttributesDropdown(int $index, string $selectedAttribute)
    {
        $preparedAttributes = [];
        foreach ($this->getAttributes() as $attribute) {
            $preparedAttribute = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];

            if ($attribute['code'] === $selectedAttribute) {
                $preparedAttribute['attrs']['selected'] = 'selected';
            }

            $preparedAttributes[] = $preparedAttribute;
        }

        $values = [
            '' => __('None'),
            [
                'label' => __('Magento Attributes'),
                'value' => $preparedAttributes,
                'attrs' => [
                    'is_magento_attribute' => true,
                ],
            ],
        ];
        $select = $this->_factoryElement->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_attribute]",
                    'values' => $values,
                    'create_magento_attribute' => true,
                ],
            ]
        );

        $select->addCustomAttribute('allowed_attribute_types', 'text,select');
        $select->addCustomAttribute('apply_to_all_attribute_sets', 'false');
        $select->addCustomAttribute('use_attribute_code_as_value', true);

        $select->setId('document-attribute-' . $index);
        $select->setForm($this->getForm());

        return $select->toHtml();
    }

    public function renderRemoveRowButton(int $index): string
    {
        $style = '';
        if (array_key_first($this->getSavedComplianceDocuments()) === $index) {
            $style = ' style="display:none"';
        }

        return sprintf(
            '<button type="button" class="action-primary %s"%s><span>%s</span></button>',
            'remove_row',
            $style,
            __('Remove')
        );
    }

    public function renderAddRowButton(): string
    {
        return sprintf(
            '<button type="button" class="action-primary %s"><span>%s</span></button>',
            'add_row',
            __('Add More')
        );
    }

    public function getTooltipHtml()
    {
        //$directionToRightClass = $directionToRight ? 'm2epro-field-tooltip-right' : '';

        $content = __('Choose an Attribute containing a valid URL for the selected Document Type');

        return <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$content}
    </div>
</div>
HTML;
    }
}
