<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Repricing extends AbstractForm
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $this->setForm($form);

        return parent::_prepareForm();
    }

}