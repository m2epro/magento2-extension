<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\StoreView;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing */
    private $listing;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->listing = $data['listing'];
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_store_view_form',
                    'action' => 'javascript:void(0)',
                    'method' => 'post',
                ],
            ]
        );

        $form->addField(
            'attention_text',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\CustomContainer::class,
            [
                'text' => (string) __(
                    <<<HTML
<div class="attention-container">
            <br>
            <p class="attention-text"><b>Attention:</b> Store View Switching may update product values on eBay!</p>
            <p class="attention-text">When you switch a store view for a listing, be aware that it can trigger an automatic update of the products. If the values of the products in the new store view are different from the current ones, those changes will be synchronized to eBay based on the rules of the synchronization policy.</p>
        </div>
HTML
                )
            ]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name' => 'id',
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_listing_fieldset',
            []
        );

        $fieldset->addField(
            'store_id',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\StoreSwitcher::class,
            [
                'name' => 'store_id',
                'class' => 'validate-no-empty M2ePro-listing-title',
                'label' => __('Store View'),
                'field_extra_attributes' => 'style="margin-top: 20px;"',
            ]
        );

        if ($this->listing->getId()) {
            $form->addValues(
                [
                    'id' => $this->listing->getId(),
                    'store_id' => $this->listing->getStoreId()
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
