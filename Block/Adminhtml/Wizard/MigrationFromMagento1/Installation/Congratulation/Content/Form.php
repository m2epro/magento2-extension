<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation\Content;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation\Content\Form
 */
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

        $fieldset->addField(
            'enable_synchronization',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Boolean::class,
            [
                'name' => 'enable_synchronization',
                'label' => $this->__('Enable M2 Synchronization'),
                'title' => $this->__('Enable M2 Synchronization'),
                'class' => 'M2ePro-required-when-visible',
                'required' => true,
                'value' => '',
                'tooltip' => $this->__(
                    '<p style="color: #41362f">If your Magento v2.x is staging yet, it is recommended to 
                    keep synchronization disabled. It can be enabled later under 
                    <i>Stores > Settings > Configuration > M2E Pro > Advanced Settings > Automatic Synchronization</i>.
                    </p>'
                )
            ]
        );

        $this->jsUrl->addUrls([
            'migrationFromMagento1/finish' =>
                $this->getUrl('m2epro/migrationFromMagento1/finish')
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
