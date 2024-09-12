<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs;

class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public function _construct(): void
    {
        parent::_construct();
        $this->setId('amazonTemplateProductTypeEditTabsTemplate');
    }

    protected function _prepareForm(): Template
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
