<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Support;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->amazonHelper = $amazonHelper;
        $this->ebayHelper = $ebayHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

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
                </ul>

                <p>Yet, if you still cannot find the answer to the issue you have faced, you can contact our
                Customer Support Team using the <strong>Contact Support</strong> form. In case your Subscription
                Plan does not include a ticket technical support, an automatic email notification about the plan
                terms and conditions will be sent to your request.</p>
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('x/O310B'),
                $this->supportHelper->getKnowledgebaseUrl()
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

        if ($this->ebayHelper->isEnabled()) {
            $values[\Ess\M2ePro\Helper\Component\Ebay::NICK]
                = $this->ebayHelper->getChannelTitle() . ' ' . $this->__('Issue');
        }

        if ($this->amazonHelper->isEnabled()) {
            $values[\Ess\M2ePro\Helper\Component\Amazon::NICK]
                = $this->amazonHelper->getChannelTitle() . ' ' . $this->__('Issue');
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
