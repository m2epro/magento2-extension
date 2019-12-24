<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form;

use Magento\Backend\Block\Widget\Form\Generic;
use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
 */
abstract class AbstractForm extends Generic
{
    const CUSTOM_CONTAINER = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\CustomContainer';
    const HELP_BLOCK       = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\HelpBlock';
    const MESSAGES         = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Messages';
    const SELECT           = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select';
    const SEPARATOR        = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Separator';
    const STORE_SWITCHER   = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\StoreSwitcher';

    use Traits\BlockTrait;
    use Traits\RendererTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;

    /** @var \Magento\Framework\Data\Form\Element\Factory */
    protected $elementFactory;

    /** @var \Magento\Cms\Model\Wysiwyg\Config */
    protected $wysiwygConfig;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->helperFactory = $context->getHelperFactory();
        $this->modelFactory = $context->getModelFactory();
        $this->activeRecordFactory = $context->getActiveRecordFactory();
        $this->parentFactory = $context->getParentFactory();
        $this->wysiwygConfig = $context->getWysiwygConfig();

        $this->css = $context->getCss();
        $this->jsPhp = $context->getJsPhp();
        $this->js = $context->getJs();
        $this->jsTranslator = $context->getJsTranslator();
        $this->jsUrl = $context->getJsUrl();

        $this->elementFactory = $context->getElementFactory();

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        \Magento\Framework\Data\Form::setFieldsetElementRenderer(
            $this->createBlock('Magento_Form_Renderer_Element')
        );

        \Magento\Framework\Data\Form::setFieldsetRenderer(
            $this->createBlock('Magento_Form_Renderer_Fieldset')
        );

        return $this;
    }
}
