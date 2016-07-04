<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'action' => 'javascript:void(0)',
                    'method' => 'post'
                ]
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}