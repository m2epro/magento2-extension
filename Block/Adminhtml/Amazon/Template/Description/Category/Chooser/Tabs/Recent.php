<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

class Recent extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'amazon/template/description/category/chooser/tabs/recent.phtml';
    protected $_selectedCategory = array();

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionCategoryChooserRecent');
        // ---------------------------------------
    }

    //########################################

    public function getCategories()
    {
        return $this->getHelper('Component\Amazon\Category')->getRecent(
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