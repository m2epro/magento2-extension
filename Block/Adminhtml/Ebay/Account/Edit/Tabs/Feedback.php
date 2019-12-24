<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback
 */
class Feedback extends AbstractForm
{

    //########################################

    protected function _prepareForm()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        $defaults = [
            'feedbacks_receive' => \Ess\M2ePro\Model\Ebay\Account::FEEDBACKS_RECEIVE_NO,
            'feedbacks_auto_response' => \Ess\M2ePro\Model\Ebay\Account::FEEDBACKS_AUTO_RESPONSE_NONE,
            'feedbacks_auto_response_only_positive' =>
                \Ess\M2ePro\Model\Ebay\Account::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_NO
        ];
        $formData = array_merge($defaults, $formData);
        $this->setData('form_data', $formData);

        $feedbacksReceive = $this->getHelper('Data')->escapeJs($formData['feedbacks_receive']);
        $feedbacksAutoResponse = $this->getHelper('Data')->escapeJs($formData['feedbacks_auto_response']);

        $form = $this->_formFactory->create();

        $form->addField(
            'ebay_accounts_feedback',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    'Choose how you want to deal with Feedback from your eBay Buyers.<br /><br />
     If you enable Import Feedback from Buyers option, you can also choose whether to set up automatic responses.
     <br /><br />
     More detailed information about ability to work with this Page you can find
     <a href="%url%" target="_blank" class="external-link">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/fQA0AQ')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'feedbacks_receive',
            'select',
            [
                'html_id' => 'feedbacks_receive',
                'name' => 'feedbacks_receive',
                'label' => $this->__('Import Feedback'),
                'values' => [
                    Account::FEEDBACKS_RECEIVE_YES => $this->__('Yes'),
                    Account::FEEDBACKS_RECEIVE_NO => $this->__('No'),
                ],
                'value' => $formData['feedbacks_receive'],
                'tooltip' => $this->__(
                    'Choose whether to Import Feedback from eBay Buyers into Magento.'
                )
            ]
        );

        $fieldset->addField(
            'feedbacks_auto_response',
            'select',
            [
                'html_id' => 'feedbacks_auto_response',
                'name' => 'feedbacks_auto_response',
                'label' => $this->__('Auto Response'),
                'class' => 'M2ePro-account-feedback-templates',
                'values' => [
                    Account::FEEDBACKS_AUTO_RESPONSE_NONE => $this->__('Disabled'),
                    Account::FEEDBACKS_AUTO_RESPONSE_CYCLED => $this->__('Cycle Mode'),
                    Account::FEEDBACKS_AUTO_RESPONSE_RANDOM => $this->__('Random Mode')
                ],
                'value' => $formData['feedbacks_auto_response'],
                'tooltip' => $this->__(
                    '<b>Cycle Mode</b> cycles through the template responses you set up below in turn.<br/>
                    <b>Random Mode</b> uses a random template response.<br/>
                    <b>Disabled</b> means no automatic responses to Feedback will be made.'
                ),
                'field_extra_attributes' => 'id="feedbacks_auto_response_container" ' .
                    (($formData['feedbacks_receive'] == Account::FEEDBACKS_RECEIVE_NO) ?
                        'style="display: none;"' : '')
            ]
        );

        $fieldset->addField(
            'feedbacks_auto_response_only_positive',
            'select',
            [
                'html_id' => 'feedbacks_auto_response_only_positive',
                'name' => 'feedbacks_auto_response_only_positive',
                'label' => $this->__('Send to'),
                'values' => [
                    Account::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_NO => $this->__('All'),
                    Account::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_YES => $this->__('Positive')
                ],
                'value' => $formData['feedbacks_auto_response_only_positive'],
                'tooltip' => $this->__(
                    'Choose whether to respond to <b>All</b> Feedback (positive, neutral or negative) or
                    <b>Positive</b> Feedback only.'
                ),
                'field_extra_attributes' => 'id="feedbacks_auto_response_only_positive_container" ' .
                    (($formData['feedbacks_receive'] == Account::FEEDBACKS_RECEIVE_NO ||
                        $formData['feedbacks_auto_response'] == Account::FEEDBACKS_AUTO_RESPONSE_NONE) ?
                        'style="display: none;"' : '')
            ]
        );

        $this->setForm($form);

        $this->js->add(<<<JS
    M2ePro.formData.feedbacks_receive = '{$feedbacksReceive}';
    M2ePro.formData.feedbacks_auto_response = '{$feedbacksAutoResponse}';

    require([
        'M2ePro/Ebay/Account',
    ], function(){
        setTimeout(function() {
            $('feedbacks_receive').observe('change', EbayAccountObj.feedbacksReceiveChange);
            $('feedbacks_auto_response').observe('change', EbayAccountObj.feedbacksAutoResponseChange);
        }, 350);
    });
JS
        );

        return parent::_prepareForm();
    }

    //########################################

    public function _toHtml()
    {
        $this->css->add(<<<CSS

.grid-listing-column-ft_id {
    width: 70px;
}

.grid-listing-column-ft_title {
    width: 500px;
}

.grid-listing-column-ft_create_date {
    width: 145px;
}

.grid-listing-column-ft_update_date {
    width: 145px;
}

.grid-listing-column-ft_action_delete {
    width: 95px;
}

#feedback_templates_grid .empty-text {
    text-align: right;
    padding: 10px;
}

#feedback_templates_grid .admin__data-grid-wrap {
    margin-bottom: 0;
    padding-bottom: 0;
}

#add_feedback_template_button_container {
    background-color: #EFEFEF;
    padding: 1em;
    margin: 0 auto;
}

#add_feedback_template_button_container > table {
    margin-left: calc(50% - 115px);
}

CSS
        );

        $addBtn = $this->createBlock('Magento\Button')
            ->setData([
                'onclick' => 'EbayAccountObj.openFeedbackTemplatePopup();',
                'label' => $this->__('Add Template'),
                'class' => 'add_feedback_template_button primary'
            ])->toHtml();

        $formData = $this->getData('form_data');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template\Grid $grid */
        $grid = $this->createBlock('Ebay_Account_Edit_Tabs_Feedback_Template_Grid');
        $gridHtml = $grid->toHtml();

        $showTemplates = (
            $formData['feedbacks_receive'] == Account::FEEDBACKS_RECEIVE_YES &&
            $formData['feedbacks_auto_response'] != Account::FEEDBACKS_AUTO_RESPONSE_NONE
        );
        $gridContainerStyle = $showTemplates ? '' : 'style="display: none;"';

        $gridStyle = $grid->getCollection()->getSize() > 0 ?
            '' : 'style="display: none;"';

        $showAddTemplateBtn = $grid->getCollection()->getSize() > 0 ?
            'style="display: none;"' : '';

        $html = parent::_toHtml();

        return <<<HTML
{$html}
<div id="feedback_templates_grid_container" {$gridContainerStyle}>
    <div id="add_feedback_template_button_container" {$showAddTemplateBtn}>

        <table style="border: none" cellpadding="0" cellspacing="0">
            <tfoot>
                <tr>
                    <td valign="middle" align="center" style="vertical-align: middle; height: 40px">
                        {$addBtn}
                    </td>
                </tr>
            </tfoot>
        </table>

    </div>
    <div id="feedback_templates_grid" {$gridStyle}>
        {$gridHtml}
        <table class="data-grid">
                <tbody>
                    <tr class="data-grid-tr-no-data even">
                        <td class="empty-text">
                            {$addBtn}
                        </td>
                    </tr>
                </tbody>
        </table>
    </div>
</div>
HTML;
    }

    //########################################
}
