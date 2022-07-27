<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditForm');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        $templateModel = $this->globalDataHelper->getValue('tmp_template')->getData();
        $formData = !empty($templateModel) ? $templateModel : ['title' => ''];

        $form = $this->_formFactory->create(['data' => [
            'id'      => 'edit_form',
            'method'  => 'post',
            'action'  => $this->getUrl('*/*/save'),
            'enctype' => 'multipart/form-data'
        ]]);

        $fieldSet = $form->addFieldset('magento_block_template_description_edit_main_general', [
            'legend' => $this->__('General'), 'collapsable' => false
        ]);

        $fieldSet->addField(
            'title',
            'text',
            [
                'name' => 'general[title]',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'value' => $formData['title'],
                'class' => 'input-text M2ePro-description-template-title',
                'required' => true,
                'tooltip' => $this->__('Short meaningful Policy Title for your internal use.')
            ]
        );

        $dataBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Data::class);
        $form->addField(
            'content_html',
            self::CUSTOM_CONTAINER,
            [
                'text' => $dataBlock->toHtml()
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
