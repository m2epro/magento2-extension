<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element as MagentoElement;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Dictionary
 */
class Dictionary extends MagentoElement
{
    public $helperFactory;

    protected $element;

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $context->getHelperFactory();
        $this->setTemplate('ebay/template/category/chooser/specific/form/renderer/dictionary.phtml');
    }

    //########################################

    public function getElement()
    {
        return $this->element;
    }

    public function render(AbstractElement $element)
    {
        $this->element = $element;
        return $this->toHtml();
    }

    //########################################

    public function getTranslator()
    {
        return $this->helperFactory->getObject('Module\Translation');
    }

    //########################################
}
