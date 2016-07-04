<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Buy;

class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setValue(0);
        $element->setDisabled('disabled');
        $element->setValues([
            ['label' => __('Disabled - Coming Soon...'), 'value' => '0']
        ]);
        return $element->getElementHtml();

    }

    public function toOptionArray()
    {
        return [
            ['label' => __('Disabled - Coming Soon...'), 'value' => '0']
        ];
    }
}