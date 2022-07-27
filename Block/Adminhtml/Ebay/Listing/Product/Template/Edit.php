<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Template;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;

        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingProductTemplatePolicy');
        $this->_controller = 'adminhtml_ebay_listing_product_template';
        $this->_mode = 'Edit';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class);
        $helpBlock->addData(
            [
                'content' => $this->__(
                    <<<HTML
        <p>You may edit Policies assigned to your Listing or create new ones. The changes you make are automatically
        applied to all M2E Pro Listings that use this Policy.</p>
        <p>Find more details on configuring Policies in the <a href="%url%" target="_blank">documentation</a>.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/_v4UB')
                ),
                'style'   => 'margin-top: 30px'
            ]
        );

        return $helpBlock->toHtml() . '<div id="content_container">' . parent::_toHtml() . '</div>';
    }
}
