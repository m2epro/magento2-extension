<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account;

class Feedback extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
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

    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_ebay_account_feedback';

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class);
        $helpBlock->setData([
            'content' => $this->__(
                <<<HTML
This page contains Feedbacks received from eBay Buyers and your responses to them.<br/><br/>
You can respond to a newly received Feedback by clicking the <strong>Send Response</strong> button for the Order.<br />
<strong>Note:</strong> Auto-responses to Feedback are sent according to the eBay Accounts > Feedback Settings.
<br /><br />
More detailed information about ability to work with this Page you can find
<a href="%url%" target="_blank" class="external-link">here</a>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('x/pf0bB')
            )
        ]);

        $this->css->add(<<<CSS

.grid-listing-column-ebay_item_id {
    width: 120px;
}

.grid-listing-column-transaction_id  {
    width: 120px;
}

.grid-listing-column-feedback_respond_status {
    width: 120px;
}

CSS
        );

        $this->js->add(<<<JS

    require([

    ], function(){

    });
JS
        );

        return
            '<div id="account_feedback_action_messages_container"></div>' .
            $helpBlock->toHtml() .
            parent::_toHtml();
    }
}
