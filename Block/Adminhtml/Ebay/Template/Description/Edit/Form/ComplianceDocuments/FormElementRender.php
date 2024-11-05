<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form\ComplianceDocuments;

class FormElementRender extends \Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element
{
    protected $_template = 'ebay/template/description/compliance_documents.phtml';

    public function getElement()
    {
        return $this->element;
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->element = $element;

        return $this->toHtml();
    }
}
