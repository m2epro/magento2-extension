<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Chooser\Tabs\Recent
 */
class Recent extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'walmart/template/category/categories/chooser/tabs/recent.phtml';
    protected $_selectedCategory = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateCategoryCategoriesChooserRecent');
        // ---------------------------------------
    }

    //########################################

    public function getCategories()
    {
        return $this->getHelper('Component_Walmart_Category')->getRecent(
            $this->getRequest()->getPost('marketplace_id'),
            [
                'product_data_nick' => $this->getRequest()->getPost('product_data_nick'),
                'browsenode_id'     => $this->getRequest()->getPost('browsenode_id'),
                'path'              => $this->getRequest()->getPost('category_path')
            ]
        );
    }

    //########################################
}
