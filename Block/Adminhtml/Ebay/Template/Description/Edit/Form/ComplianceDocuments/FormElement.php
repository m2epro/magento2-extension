<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form\ComplianceDocuments;

use Ess\M2ePro\Model\Ebay\Template\Description;

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
            return $this->getEmptyDocuments();
        }

        return $documents;
    }

    public function getEmptyDocuments(): array
    {
        return [
            [
                'document_type' => '',
                'document_attribute' => '',
                'document_languages' => [],
                'document_mode' => Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE
            ],
        ];
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
                    'class' => 'document-type',
                    'values' => $values,
                ],
            ]
        );

        $select->setId('document-type-' . $index);
        $select->setForm($this->getForm());

        return $select->toHtml();
    }

    public function renderModeDropdown(int $index, int $documentMode)
    {
        $values = [
            [
                'value' => \Ess\M2ePro\Model\Ebay\Template\Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE,
                'label' => __('Magento Attribute')],
            [
                'value' => \Ess\M2ePro\Model\Ebay\Template\Description::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE,
                'label' => __('Custom Value')
            ]
        ];

        $select = $this->_factoryElement->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_mode]",
                    'class' => 'document-mode',
                    'values' => $values,
                    'value' => $documentMode
                ],
            ]
        );

        $select->setId('document-mode-' . $index);
        $select->setForm($this->getForm());

        return $select->toHtml();
    }

    public function renderAttributesDropdown(int $index, string $selectedAttribute, int $mode)
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

        $isHide = $mode !== \Ess\M2ePro\Model\Ebay\Template\Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE;

        $select = $this->_factoryElement->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_attribute]",
                    'class' => 'document-magento-attribute',
                    'style' => $isHide ? 'display: none' : '',
                    'values' => $preparedAttributes,
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

    public function renderLanguagesDropdown(int $index, array $selectedLanguages)
    {
        $languages = \Ess\M2ePro\Model\Ebay\ComplianceDocuments::getDocumentLanguages();

        $values = [];
        foreach ($languages as $language => $label) {
            $values[] = ['label' => $label, 'value' => $language];
        }

        $select = $this->_factoryElement->create(
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Multiselect::class,
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_languages]",
                    'values' => $values,
                    'value' => $selectedLanguages,
                    'size' => 3,
                    'class' => 'M2ePro-compliance-document-language-validator'
                ],
            ]
        );

        $select->setId('document-languages-' . $index);
        $select->setForm($this->getForm());

        return $select->toHtml();
    }

    public function renderCustomValueInput(int $index, $customValue, $mode)
    {
        $isHide = $mode !== \Ess\M2ePro\Model\Ebay\Template\Description::COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE;

        $input = $this->_factoryElement->create(
            'text',
            [
                'data' => [
                    'name' => "description[compliance_documents][$index][document_custom_value]",
                    'value' => $customValue,
                    'style' => 'width: 100%;' . ($isHide ? ' display:none;' : ''),
                    'class' => 'document-custom-value'
                ],
            ]
        );

        $input->setId('document-custom-value-' . $index);
        $input->setForm($this->getForm());

        return $input->toHtml();
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

    public function getTooltipHtml(string $text): string
    {
        return <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$text}
    </div>
</div>
HTML;
    }
}
