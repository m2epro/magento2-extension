<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs;

class OptionMapping extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    private const FORM_ID = 'ebay_variation_option_mapping_form';
    private const MESSAGES_BLOCK_ID = 'ebay_variation_option_mapping_messages';
    private const SAVE_BUTTON_ID = 'save_variation_option_mapping_button';

    private \Ess\M2ePro\Model\Listing\Product $listingProduct;
    private array $variationOptions;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $variationOptions,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->listingProduct = $listingProduct;
        $this->variationOptions = $variationOptions;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('eabyOptionMapping');
        $this->_mode = 'edit';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        $this->addButton('save', [
            'label' => __('Save'),
            'id' => self::SAVE_BUTTON_ID,
            'class' => 'action-primary',
        ]);
    }

    protected function _prepareLayout()
    {
        $formId = self::FORM_ID;
        $messagesBlockId = self::MESSAGES_BLOCK_ID;
        $saveButtonId = self::SAVE_BUTTON_ID;

        $css = <<<CSS
#$formId .admin__field {
    margin-bottom: 0 !important;
}

label.ebay-mapping-option-error {
    display: initial !important;
    width: auto !important;
    margin-left: 1.5rem !important;
    padding: 0.7rem 1.5rem 0.7rem 0.7rem !important;
}
CSS;

        $this->css->add($css);

        $jsScript = <<<JS
require([
        'M2ePro/Ebay/Listing/Product/Variation/Manage/Tabs/OptionsConfiguration',
    ], function(){
        window.EbayListingProductVariationManageTabsOptionsConfigurationObj = new EbayListingProductVariationManageTabsOptionsConfiguration({
            formId: '$formId',
            saveButtonId: '$saveButtonId',
            messagesBlockId: '$messagesBlockId',
        });
    });
JS;

        $this->js->add($jsScript);

        $formBlock = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\Tabs\OptionsConfiguration\Form::class,
                '',
                [
                    'listingProduct' => $this->listingProduct,
                    'variationOptions' => $this->variationOptions,
                    'formId' => $formId,
                ]
            );

        $this->setChild('form', $formBlock);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $helpBlockContent = __('Map your Magento option values to the values supported by eBay. ' .
            'Mapped values are sent to eBay instead of the original ones, and your Magento product stays unchanged. ' .
            'Values left as "Not Set" are sent to eBay as they are.<br><br>eBay requires standard values only for ' .
            'the Size attribute. Other attribute options can stay unmapped.');

        $helpBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)
            ->setData([
                'content' => $helpBlockContent,
            ]);

        return $helpBlock->toHtml() . '<div id="' . self::MESSAGES_BLOCK_ID . '"></div>' . parent::_toHtml();
    }
}
