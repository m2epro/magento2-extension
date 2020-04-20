<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\AboutModule
 */
class AboutModule extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

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
                'text' => $this->getHelper('Module')->getPublicVersion()
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
                'text' => ucfirst($this->getHelper('Magento')->getEditionName())
            ]
        );

        $fieldSet->addField(
            'magento_version',
            'note',
            [
                'label' => $this->__('Version'),
                'text' => $this->getHelper('Magento')->getVersion()
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################
}
