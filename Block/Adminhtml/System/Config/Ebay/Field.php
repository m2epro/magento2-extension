<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Ebay;

class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $moduleHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        array $data = []
    )
    {
        $this->moduleHelper = $moduleHelper;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setValue((int)$this->moduleHelper->getConfig()->getGroupValue(
            '/component/ebay/', 'mode'
        ));
        $element->setValues([
            ['label' => __('Disabled'), 'value' => '0'],
            ['label' => __('Enabled'), 'value' => '1'],
        ]);
        return $element->getElementHtml();

    }

    public function toOptionArray()
    {
        return [
            ['label' => __('Disabled'), 'value' => '0'],
            ['label' => __('Enabled'), 'value' => '1'],
        ];
    }
}