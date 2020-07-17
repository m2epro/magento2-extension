<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Tabs\Search
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayCategoryChooserCategorySearch');
        $this->setTemplate('ebay/template/category/chooser/tabs/search.phtml');
    }

    //########################################
}
