<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Tabs;

class Recent extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Category */
    protected $categoryHelper;

    protected $_template = 'amazon/template/description/category/chooser/tabs/recent.phtml';
    protected $_selectedCategory = [];

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Category $categoryHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->categoryHelper = $categoryHelper;
    }

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
        return $this->categoryHelper->getRecent(
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
