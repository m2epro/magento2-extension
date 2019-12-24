<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select
 */
class Select extends \Magento\Framework\Data\Form\Element\Select
{
    /**
     * Get the element Html.
     *
     * @return string
     */
    public function getElementHtml()
    {
        $this->addClass('select admin__control-select');

        if ($this->getData('create_magento_attribute') === true) {
            $this->addClass('M2ePro-custom-attribute-can-be-created');
        }

        $html = '';
        if ($this->getBeforeElementHtml()) {
            $html .= '<label class="addbefore" for="' .
                $this->getHtmlId() .
                '">' .
                $this->getBeforeElementHtml() .
                '</label>';
        }

        $html .= '<select id="' . $this->getHtmlId() . '" name="' . $this->getName() . '" ' . $this->serialize(
            $this->getHtmlAttributes()
        ) . $this->_getUiId() . '>' . "\n";

        $value = $this->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }

        if ($values = $this->getValues()) {
            foreach ($values as $key => $option) {
                if (!is_array($option)) {
                    $html .= $this->_optionToHtml(['value' => $key, 'label' => $option], $value);
                } elseif (is_array($option['value'])) {
                    $html .= '<optgroup label="' . $option['label'] . '" '
                             . $this->addCustomOptGroupAttributes($option)
                             . ' >' . "\n";
                    foreach ($option['value'] as $groupItem) {
                        $html .= $this->_optionToHtml($groupItem, $value);
                    }
                    $html .= '</optgroup>' . "\n";
                } else {
                    $html .= $this->_optionToHtml($option, $value);
                }
            }
        }

        $html .= '</select>' . "\n";
        if ($this->getAfterElementHtml()) {
            $html .= '<label class="addafter" for="' .
                $this->getHtmlId() .
                '">' .
                "\n{$this->getAfterElementHtml()}\n" .
                '</label>' .
                "\n";
        }
        return $html;
    }

    /**
     * Format an option as Html
     *
     * @param array $option
     * @param array $selected
     * @return string
     */
    protected function _optionToHtml($option, $selected)
    {
        if (is_array($option['value'])) {
            $html = '<optgroup label="' . $option['label'] . '" '
                    . $this->addCustomOptGroupAttributes($option)
                    . ' >' . "\n";
            foreach ($option['value'] as $groupItem) {
                $html .= $this->_optionToHtml($groupItem, $selected);
            }
            $html .= '</optgroup>' . "\n";
        } else {
            $html = '<option value="' . $this->_escape($option['value']) . '"';
            $html .= isset($option['title']) ? 'title="' . $this->_escape($option['title']) . '"' : '';
            $html .= isset($option['style']) ? 'style="' . $option['style'] . '"' : '';
            $html .= $this->addCustomOptionAttributes($option);
            if (in_array($option['value'], $selected)) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $this->_escape($option['label']) . '</option>' . "\n";
        }
        return $html;
    }

    protected function addCustomOptGroupAttributes(array $attributeData)
    {
        if ($this->getData('create_magento_attribute') === true
            && !empty($attributeData['attrs']['is_magento_attribute'])) {
            if (empty($attributeData['attrs']) || !is_array($attributeData['attrs'])) {
                $attributeData['attrs'] = ['class' => 'M2ePro-custom-attribute-optgroup'];
            } else {
                if (isset($attributeData['attrs']['class'])) {
                    $attributeData['attrs']['class'] = $attributeData['attrs']['class']
                                                       . ' M2ePro-custom-attribute-optgroup';
                } else {
                    $attributeData['attrs']['class'] = 'M2ePro-custom-attribute-optgroup';
                }
            }

            unset($attributeData['attrs']['is_magento_attribute']);
        }

        return $this->addCustomOptionAttributes($attributeData);
    }

    protected function addCustomOptionAttributes(array $attributeData)
    {
        if (empty($attributeData['attrs']) || !is_array($attributeData['attrs'])) {
            return '';
        }

        $html = '';
        foreach ($attributeData['attrs'] as $name => $value) {
            $html .= ' ' . $name . '="' . $value . '"';
        }

        return $html;
    }

    /**
     * Get the Html attributes.
     *
     * @return string[]
     */
    public function getHtmlAttributes()
    {
        return [
            'title',
            'class',
            'style',
            'onclick',
            'onchange',
            'disabled',
            'readonly',
            'tabindex',
            'data-form-part',
            'data-role',
            'data-action',
            'data-mode'
        ];
    }
}
