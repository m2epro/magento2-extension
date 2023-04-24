<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs;

class Template extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonTemplateProductTypeEditTabsTemplate');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs\Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm(): Template
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
