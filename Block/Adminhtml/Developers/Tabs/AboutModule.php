<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

class AboutModule extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $fieldSet = $form->addFieldset(
            'field_module',
            [
                'legend' => $this->__('Module'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField(
            'm2e_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text' => $this->moduleHelper->getPublicVersion()
            ]
        );

        $fieldSet = $form->addFieldset(
            'field_magento',
            [
                'legend' => $this->__('Magento'),
                'collapsable' => false
            ]
        );

        $fieldSet->addField(
            'magento_edition',
            'note',
            [
                'label' => $this->__('Edition'),
                'text' => ucfirst($this->magentoHelper->getEditionName())
            ]
        );

        $fieldSet->addField(
            'magento_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text' => $this->magentoHelper->getVersion()
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
