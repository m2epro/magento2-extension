<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Form\Element;

class StateControlButton extends \Magento\Framework\Data\Form\Element\Button
{
    use AbstractElementTrait;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setData('class', 'action-default scalable action-primary');
        $this->setData('title', $this->getData('content'));
    }

    /**
     * @return string
     */
    public function getElementHtml(): string
    {
        $html = <<<HTML
<button id="button_{$this->getHtmlId()}" {$this->serialize($this->getHtmlAttributes())}>
    <span>{$this->getData('content')}</span>
</button>
<input id="{$this->getHtmlId()}" type="hidden"
    name="{$this->getData('name')}" value="{$this->getData('value')}">
HTML;

        $tooltip = $this->getData('tooltip');
        if ($tooltip !== null && $tooltip !== '') {
            return $html . $this->getTooltipHtml($this->getData('tooltip'));
        }

        return $html;
    }
}
