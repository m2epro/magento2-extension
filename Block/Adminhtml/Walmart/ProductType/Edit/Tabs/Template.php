<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs;

class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('walmartProductTypeEditTabsTemplate');
    }

    protected function _prepareForm(): Template
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
