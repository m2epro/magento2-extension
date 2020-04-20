<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\System\Config\Integration
 */
abstract class Integration extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setValues($this->getOptionsArray());

        return parent::_getElementHtml($element);
    }

    /**
     * @inheritdoc
     */
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _renderScopeLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return '';
    }

    protected function getOptionsArray()
    {
        return [
            ['label' => __('Disabled'), 'value' => '0'],
            ['label' => __('Enabled'), 'value' => '1'],
        ];
    }
}
