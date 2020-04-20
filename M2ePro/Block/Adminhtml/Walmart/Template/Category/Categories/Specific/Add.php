<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add
 */
class Add extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected $_template = 'walmart/template/category/categories/specific/add.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_walmart_template_category_categories_specific_add';

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesSpecificAdd');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
        // ---------------------------------------
    }

    //########################################

    protected function _beforeToHtml()
    {
        $pathParts = explode('/', ltrim($this->getRequest()->getParam('current_indexed_xpath'), '/'));
        array_walk($pathParts, function (&$el) {
            $el = preg_replace('/-\d+/', '', $el);
            $el = preg_replace('/(?<!^)[A-Z0-9]/', ' $0', $el);
            $el = ucfirst($el);
        });

        $additionalTitle = implode(' > ', $pathParts);
        $this->setData('additional_title', $this->escapeHtml($additionalTitle));

        return parent::_beforeToHtml();
    }

    //########################################
}
