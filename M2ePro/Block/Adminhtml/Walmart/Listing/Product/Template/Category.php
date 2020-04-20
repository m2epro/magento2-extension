<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template
{
    protected $newAsin = false;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('walmart/listing/product/template/category.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                '
    From the list below, select the relevant Category Policy for your Products.<br>
    Press Add New Category Policy, to create a new Category Policy template.<br><br>

    The detailed information can be found <a href="%url%" target="_blank">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/RQBhAQ')
            )
        ]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }

    //########################################
}
