<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ProductsPrimary
 */
class ProductsPrimary extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryViewProductsPrimary');
        $this->_controller = 'adminhtml_ebay_category_view_tabs_productsPrimary';

        $this->_headerText = '';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton('back', [
            'label'     => $this->__('Back'),
            'class'     => 'back',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/index') . '\');'
        ]);
    }

    //########################################
}