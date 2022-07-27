<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template;

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

    protected function _prepareForm()
    {
        $template = $this->globalDataHelper->getValue('edit_template');

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_feedback_template_form',
                'action' => 'javascript:void(0)',
                'method' => 'post',
            ]]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name' => 'id'
            ]
        );

        $form->addField(
            'account_id',
            'hidden',
            [
                'name' => 'account_id',
                'value' => $this->getRequest()->getParam('account_id')
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_feedback_template',
            []
        );

        $fieldset->addField(
            'body',
            'textarea',
            [
                'name' => 'body',
                'required' => true,
                'label' => $this->__('Message'),
                'field_extra_attributes' => 'style="margin-top: 30px;"'
            ]
        );

        if ($template) {
            $form->addValues($template->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
