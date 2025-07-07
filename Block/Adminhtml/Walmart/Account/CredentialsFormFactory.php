<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account;

use Ess\M2ePro\Helper\Component\Walmart;

class CredentialsFormFactory extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \Ess\M2ePro\Helper\Component\Walmart $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function create(bool $withTitle, bool $withButton, string $id, string $submitUrl = '')
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => $id,
                    'action' => $submitUrl,
                    'method' => 'post',
                ],
            ]
        );

        $form->setUseContainer(true);

        $fieldset = $form->addFieldset(
            'general_credentials',
            [
                'legend' => __('Add API Keys'),
                'collapsable' => false,
                'class' => 'fieldset admin__fieldset admin__field-control',
            ],
        );

        $fieldset->addField(
            'specific_end_url',
            'hidden',
            [
                'name'  => 'specific_end_url',
                'value' => $this->getRequest()->getParam('specific_end_url'),
            ]
        );

        $fieldset->addField(
            'marketplace_id',
            'hidden',
            [
                'name'  => 'marketplace_id',
                'value' => $this->getRequest()->getParam('marketplace_id'),
            ]
        );

        if ($withTitle) {
            $fieldset->addField(
                'title',
                'text',
                [
                    'name' => 'title',
                    'class' => 'M2ePro-account-title',
                    'label' => __('Title'),
                    'required' => true,
                    'value' => '',
                ]
            );
        }

        $marketplaceCA = Walmart::MARKETPLACE_CA;
        $fieldset->addField(
            'marketplaces_register_url_ca',
            'link',
            [
                'label' => '',
                'href' => $this->walmartHelper->getRegisterUrl($marketplaceCA),
                'target' => '_blank',
                'value' => __('Get Access Data'),
                'class' => "external-link",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
            ]
        );

        $fieldset->addField(
            'consumer_id',
            'text',
            [
                'container_id' => 'marketplaces_consumer_id_container',
                'name' => 'consumer_id',
                'label' => __('Consumer ID'),
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required' => true,
                'value' => '',
                'tooltip' => __('A unique seller identifier on the website.'),
            ]
        );

        $fieldset->addField(
            'private_key',
            'textarea',
            [
                'container_id' => 'marketplaces_private_key_container',
                'name' => 'private_key',
                'label' => __('Private Key'),
                'class' => "M2ePro-marketplace-merchant",
                'css_class' => "marketplace-required-field marketplace-required-field-id{$marketplaceCA}",
                'required' => true,
                'value' => '',
                'tooltip' => __('Walmart Private Key generated from your Seller Center Account.'),
            ]
        );

        if ($withButton) {
            $fieldset->addField(
                'submit_button',
                'submit',
                [
                    'value' => __('Save'),
                    'style' => '',
                    'class' => 'submit action-default action-primary',
                ]
            );
        }

        return $form;
    }
}
