<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Boolean
 */
class Boolean extends \Magento\Framework\Data\Form\Element\Select
{
    //########################################

    protected function _construct()
    {
        parent::_construct();
        $this->setValues([
            ['label' => '', 'value' => ''],
            ['label' => __('No'), 'value' => 0],
            ['label' => __('Yes'), 'value' => 1]
        ]);
    }

    protected function _optionToHtml($option, $selected)
    {
        if (is_array($option['value'])) {
            $html = '<optgroup label="' . $option['label'] . '">' . "\n";
            foreach ($option['value'] as $groupItem) {
                $html .= $this->_optionToHtml($groupItem, $selected);
            }
            $html .= '</optgroup>' . "\n";
        } else {
            $html = '<option value="' . $this->_escape($option['value']) . '"';
            $html .= isset($option['title']) ? 'title="' . $this->_escape($option['title']) . '"' : '';
            $html .= isset($option['style']) ? 'style="' . $option['style'] . '"' : '';
            if (in_array($option['value'], $selected, true)) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $this->_escape($option['label']) . '</option>' . "\n";
        }
        return $html;
    }

    //########################################
}
