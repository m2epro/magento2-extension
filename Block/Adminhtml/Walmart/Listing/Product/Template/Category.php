<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template;

class Category extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template
{
    protected $newAsin = false;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('walmart/listing/product/template/category.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                '
    From the list below, select the relevant Category Policy for your Products.<br>
    Press Add New Category Policy, to create a new Category Policy template.<br><br>

    The detailed information can be found <a href="%url%" target="_blank">here</a>.',
                $this->supportHelper->getDocumentationArticleUrl('x/bf1IB')
            )
        ]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }

    //########################################
}
