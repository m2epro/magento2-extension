<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Support;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Support\Form
 */
class Form extends AbstractForm
{
    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $this->__(
                <<<HTML
                <p>We strongly recommend you to review the detailed documentation, specially created for
                the M2E Pro Clients, to find answers to your questions as many solutions have already been
                described. You can use the following resources:</p>

                <ul>
                <li><p><a href="%url_1%" target="_blank" class="external-link">Documentation</a>
                - structured documents containing detailed
                instructions on how to use M2E Pro Extension;</p></li>
                <li><p><a href="%url_2%" target="_blank" class="external-link">Knowledge Base</a>
                - a collection of articles
                describing the causes of the common errors as well as the solutions to the frequently
                asked questions;</p></li>
                <li><p><a href="%url_3%" target="_blank" class="external-link">Ideas Workshop</a>
                - a base of notices where you
                can find other Users’ suggestions as well as offer your idea about a new feature which
                could be useful in M2E Pro;</p></li>
                <li><p><a href="%url_4%" target="_blank" class="external-link">Community Forum</a>
                - an open M2E Pro discussion forum
                where our Users discuss and search for solutions together.</p></li>
                </ul>

                <p>Yet, if you still cannot find the answer to the issue you have faced, you can contact our
                Customer Support Team using the <strong>Contact Support</strong> form. In case your Subscription
                Plan does not include a ticket technical support, an automatic email notification about the plan
                terms and conditions will be sent to your request.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/u4AVAQ'),
                $this->getHelper('Module\Support')->getKnowledgebaseUrl(),
                $this->getHelper('Module\Support')->getIdeasUrl(),
                $this->getHelper('Module\Support')->getForumUrl()
            )
        ]);

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'    => 'edit_form',
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'contact_us',
            []
        );

        $fieldset->addField(
            'subject',
            'text',
            [
                'name' => 'subject',
                'required' => true,
                'label' => $this->__('Subject'),
            ]
        );

        $fieldset->addField(
            'contact_name',
            'text',
            [
                'name' => 'contact_name',
                'required' => true,
                'label' => $this->__('Contact Name'),
            ]
        );

        $fieldset->addField(
            'contact_mail',
            'text',
            [
                'name' => 'contact_mail',
                'required' => true,
                'class' => 'M2ePro-validate-email',
                'label' => $this->__('Contact Email'),
            ]
        );

        $values = [
            'none' => $this->__('General Issue')
        ];

        if ($this->getHelper('Component\Ebay')->isEnabled()) {
            $values[\Ess\M2ePro\Helper\Component\Ebay::NICK]
                = $this->getHelper('Component\Ebay')->getChannelTitle() . ' ' . $this->__('Issue');
        }

        if ($this->getHelper('Component\Amazon')->isEnabled()) {
            $values[\Ess\M2ePro\Helper\Component\Amazon::NICK]
                = $this->getHelper('Component\Amazon')->getChannelTitle() . ' ' . $this->__('Issue');
        }

        $referrer = $this->getRequest()->getParam('referrer', false);
        if (count($values) > 1 && !$referrer) {
            $fieldset->addField(
                'component',
                'select',
                [
                    'name' => 'component',
                    'label' => $this->__('Problem Type'),
                    'values' => $values,
                    'value' => 'none'
                ]
            );
        } else {
            $fieldset->addField(
                'component',
                'hidden',
                [
                    'name' => 'component',
                    'value' => $referrer
                ]
            );
        }

        $fieldset->addField(
            'description',
            'textarea',
            [
                'label' => $this->__('Description'),
                'name' => 'description',
                'style' => 'height: 250px',
                'value' => <<<TEXT
What steps will reproduce the problem?
1.
2.
3.

What is the expected output? What do you see instead?

Please provide any additional information below.
TEXT
            ]
        );

        $fieldset->addField(
            'files',
            'file',
            [
                'css_class' => 'no-margin-bottom',
                'container_id' => 'more_button_container',
                'label' => $this->__('Attachment'),
                'name' => 'files[]',
                'onchange' => 'SupportObj.toggleMoreButton()',
            ]
        );

        $fieldset->addField(
            'more_attachments',
            'button',
            [
                'container_id' => 'more_attachments_container',
                'label' => '',
                'value' => $this->__('Attach Another File'),
                'class' => 'action-default',
                'onclick' => 'SupportObj.moreAttachments()'
            ]
        );

        $fieldset->addField(
            'send_button',
            'button',
            [
                'label' => '',
                'value' => $this->__('Submit'),
                'class' => 'action-primary right',
                'onclick' => 'SupportObj.saveClick()'
            ]
        );

        $params = [];
        $referrer && $params['referrer'] = $referrer;

        $this->jsUrl->add($this->getUrl('*/support/save', $params), 'formSubmit');

        $this->js->add(<<<JS
    require([
        'M2ePro/Support',
    ], function(){
        window.SupportObj = new Support();
    });
JS
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
