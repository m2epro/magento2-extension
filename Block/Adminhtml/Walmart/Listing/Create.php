<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

class Create extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingCreateStepOne');
        $this->_controller = 'adminhtml_walmart_listing';
        $this->_mode       = 'create';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__("Creating A New Walmart M2E Pro Listing");
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
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

        $this->jsUrl->add(
            $this->getUrl(
                '*/walmart_listing_create/index',
                [
                    '_current' => true
                ]
            ),
            'walmart_listing_create/index'
        );
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData(
            [
                'content' => $this->__(
                    'On this page, you can configure the basic Listing settings. Specify the meaningful Listing Title for
                your internal use.<br>
                Select Account under which you want to manage this Listing. Assign the Policy Templates and
                Magento Store View.<br/><br/>
                <p>The detailed information can be found <a href="%url%" target="_blank">here</a></p>',
                    $this->supportHelper->getDocumentationArticleUrl('x/Xf1IB')
                )
            ]
        );

        return
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }

    //########################################
}
