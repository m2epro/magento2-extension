<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Button;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown
 */
class DropDown extends \Magento\Backend\Block\Widget\Button\SplitButton
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Ess_M2ePro::magento/button/dropdown.phtml');
    }

    /**
     * @param array $option
     * @param string $title
     * @param string $classes
     * @param string $disabled
     * @return array
     */
    protected function _prepareOptionAttributes($option, $title, $classes, $disabled)
    {
        $attributes = [
            'id' => isset($option['id']) ? $this->getId() . '-' . $option['id'] : '',
            'title' => $title,
            'class' => join(' ', $classes),
            'onclick' => isset($option['onclick']) ? $option['onclick'] : '',
            'style' => isset($option['style']) ? $option['style'] : '',
            'disabled' => $disabled,
        ];

        if (isset($option['data_attribute'])) {
            $this->_getDataAttributes($option['data_attribute'], $attributes);
        }

        return $attributes;
    }

    //########################################
}
