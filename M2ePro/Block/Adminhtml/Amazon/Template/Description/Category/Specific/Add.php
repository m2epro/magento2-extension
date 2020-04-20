<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific\Add
 */
class Add extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    protected $_template = 'amazon/template/description/category/specific/add.phtml';

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_template_description_category_specific_add';

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategorySpecificAdd');
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
        $additionalTitle = $this->getRequest()->getParam('current_indexed_xpath');
        $additionalTitle = explode('/', ltrim($additionalTitle, '/'));
        array_shift($additionalTitle);
        $additionalTitle = array_map(function ($el) {
            return preg_replace('/-\d+/', '', $el);
        }, $additionalTitle);
        $this->setData('additional_title', implode(' > ', $additionalTitle));

        return parent::_beforeToHtml();
    }

    //########################################
}
