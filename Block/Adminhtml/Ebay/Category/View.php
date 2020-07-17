<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category;

use Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryView');
        $this->_template = Module::IDENTIFIER . '::ebay/category/view.phtml';

        $this->removeButton('back');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->setChild('info', $this->createBlock(
            'Ebay_Category_View_Info',
            '',
            ['data' => ['template_id' => $this->getRequest()->getParam('template_id')]]
        ));

        $this->setChild(
            'tabs',
            $this->createBlock('Ebay_Category_View_Tabs')
        );

        return parent::_prepareLayout();
    }

    //########################################

    /**
     * @return string
     */
    public function getInfoHtml()
    {
        return $this->getChildHtml('info');
    }

    /**
     * @return string
     */
    public function getTabsHtml()
    {
        return $this->getChildHtml('tabs');
    }

    //########################################
}
