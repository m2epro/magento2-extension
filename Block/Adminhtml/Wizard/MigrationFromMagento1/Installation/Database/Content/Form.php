<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database\Content;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
            'method' => 'post',
        ]]);

        $fieldset = $form->addFieldset('general', ['legend' => '']);

        if (!$this->getHelper('Data\Session')->getValue('unexpected_migration_m1_url')) {

            /** @var MigrationFromMagento1 $wizard */
            $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);

            $fieldset->addField(
                'magento_1_url',
                'text',
                [
                    'name' => 'magento_1_url',
                    'label' => $this->__('M1 Website Address'),
                    'title' => $this->__('M1 Website Address'),
                    'required' => true,
                    'value' => $wizard->getPossibleM1Domain()
                ]
            );
        }

        $fieldset->addField(
            'disable_m1_module',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Boolean::class,
            [
                'name' => 'disable_m1_module',
                'label' => $this->__('Disable M1 Synchronization'),
                'title' => $this->__('Disable M1 synchronization'),
                'class' => 'M2ePro-required-when-visible',
                'required' => true,
                'value' => '',
                'tooltip' => $this->__(
                    '<p style="color: #41362f">If your Magento v2.x is staging yet, 
                    it is recommended to keep synchronization on Magento v1.x running. It can be disabled later under 
                    <i>System > Configuration > M2E Pro > Module & Channels > Module
                     > Automatic Synchronization</i>.</p>'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
