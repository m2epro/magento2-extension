<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
 */
abstract class AbstractHorizontalTabs extends AbstractTabs
{
    protected $_template = 'Magento_Backend::widget/tabshoriz.phtml';

    /**
     * May be temporarily solution
     * Prevent displaying not processed tabs by JS widget
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->css->add("#{$this->getId()} ul { display: none; }");

        $this->js->addOnReadyJs("jQuery('#{$this->getId()} ul').show();");

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        return
            '<div class="m2epro-tabs-horizontal">' .
            parent::_toHtml() .
            '</div>';
    }
}
