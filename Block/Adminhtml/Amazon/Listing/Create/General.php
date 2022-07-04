<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
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
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingCreateGeneral');
        $this->_controller = 'adminhtml_amazon_listing_create';
        $this->_mode = 'general';

        $this->_headerText = $this->__("Creating A New Amazon M2E Pro Listing");

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton(
            'save_and_next',
            [
                'label' => $this->__('Next Step'),
                'class' => 'action-primary forward'
            ]
        );
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        $breadcrumb = $this->getLayout()
                           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Breadcrumb::class)
            ->setSelectedStep(1);

        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData(
            [
                'content' => $this->__(
                    '<p>It is necessary to select an Amazon Account (existing or create a new one) as well as choose
                a Marketplace that you are going to sell Magento Products on.</p>
                <p>It is also important to specify a Store View in accordance with which Magento Attribute
                values will be used in the Listing settings.</p><br>
                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->supportHelper->getDocumentationArticleUrl('x/hf8UB')
                )
            ]
        );

        return
            $breadcrumb->toHtml() .
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }
}
