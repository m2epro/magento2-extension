<?php

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Separator extends AbstractElement
{
    protected function _construct()
    {
        parent::_construct();
        $this->setType('hidden');
    }

    public function getElementHtml()
    {
        $this->addClass('m2epro-separator');

        return <<<HTML
<div id="{$this->getHtmlId()}" class="{$this->getClass()}">
    <hr/>
</div>
HTML;
    }
}