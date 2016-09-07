<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Buy;

class Field extends \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
{
    /**
     * @inheritdoc
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDisabled('disabled');

        return parent::_getElementHtml($element);
    }

    public function getOptionsArray()
    {
        return [
            ['label' => __('Disabled - Coming Soon...'), 'value' => '0']
        ];
    }
}