<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License\Change
 */
class Change extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\License */
    private $helperModuleLicense;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\License $helperModuleLicense,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->helperModuleLicense = $helperModuleLicense;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => 'javascript:void(0)'
            ]
        ]);

        $fieldSet = $form->addFieldset('change_license', ['legend' => '', 'collapsable' => false]);

        $key = $this->dataHelper->escapeHtml($this->helperModuleLicense->getKey());
        $fieldSet->addField(
            'new_license_key',
            'text',
            [
                'name' => 'new_license_key',
                'label' => $this->__('New License Key'),
                'title' => $this->__('New License Key'),
                'value' => $key,
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}
