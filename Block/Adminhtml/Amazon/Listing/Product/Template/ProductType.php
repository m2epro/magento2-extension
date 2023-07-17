<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template;

class ProductType extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('amazon/listing/product/product_type/popup.phtml');
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductType
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeToHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                'Product Type is used for creating a new ASIN/ISBN on Amazon.
                To assign it, select either an existing Product Type or create a new one.<br><br>
                Learn how to manage Amazon Product Types in
                <a href="%url%" target="_blank" class="external-link">this article</a>',
                $this->supportHelper->getDocumentationArticleUrl('description-policies')
            ),
        ]);

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }
}
