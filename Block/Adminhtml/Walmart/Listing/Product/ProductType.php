<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;

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
        $this->setTemplate('walmart/listing/product/product_type.phtml');
    }

    protected function _beforeToHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                '
    From the list below, select the relevant Product Type for your Products.<br>
    Press Add New Product Type, to create a new Product Type.<br><br>

    The detailed information can be found <a href="%url%" target="_blank">here</a>.',
                $this->supportHelper->getDocumentationArticleUrl(
                    'help/m2/walmart-integration/m2e-pro-listing-set-up/configuring-policies/category-policy'
                )
            ),
        ]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }
}
