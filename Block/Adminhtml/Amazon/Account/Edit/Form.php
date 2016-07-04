<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'action' => $this->getUrl('*/*/save'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}