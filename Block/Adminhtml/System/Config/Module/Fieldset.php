<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Module;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\System\Config\Module\Fieldset
 */
class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Return header title part of html for fieldset
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        return '<a id="' .
        $element->getHtmlId() .
        '-head" href="#' .
        $element->getHtmlId() .
        '-link" onclick="return false;">' . $element->getLegend() . '</a>';
    }

    /**
     * Get collapsed state on-load
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return true;
    }
}
