<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode
 */
class Mode extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $formFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->formFactory = $formFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionMode');
        // ---------------------------------------
    }

    //########################################

    public function isAdminStore()
    {
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing');

        return $listing->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    public function getWebsiteName()
    {
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing');

        return $this->getHelper('Magento\Store')->getWebsiteName($listing->getStoreId());
    }

    public function getHelpPageUrl()
    {
        return '';
    }

    //########################################

    protected function _toHtml()
    {
        return $this->getHelpBlock()->toHtml()
                . '<h3 id="block-title-top">' . $this->getBlockTitle() . '</h3>'
                . '<div id="block-content-wrapper" style="margin-left: 26px">'.$this->getBlockContent().'</div>'
                . parent::_toHtml();
    }

    // ---------------------------------------

    protected function getBlockTitle()
    {
        return $this->__(
            'Choose the level at which Products should be automatically added or deleted'
        );
    }

    protected function getHelpBlock()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'id' => 'block_notice_listing_auto_action_mode',
            'content' => $this->__(
                '<p>Choose the level at which Products should be automatically added or deleted.</p><br>

                <p><strong>Global</strong> will check for Products being added or deleted in Magento Catalog.</p>
                <p><strong>Website</strong> will check for Products being added or deleted in Magento Website.</p>
                <p><strong>Category</strong> will check for Products being added or deleted in Magento Category.</p>
                <br>

                <p>You can always modify the add/remove settings by clicking on
                Edit Settings > Auto Add/Remove Rules button in your M2E Pro Listing.</p>

                <p>More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                $this->getHelpPageUrl()
            )
        ]);

        return $helpBlock;
    }

    protected function getBlockContent()
    {
        $form = $this->formFactory->create();

        $form->addField(
            'global',
            'radio',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL,
                'class' => 'admin__control-radio',
                'after_element_html' => $this->__('Global (all Products)')
            ]
        );

        $form->addField(
            'note_global',
            'note',
            [
                'text' => $this->__('Acts when a Product is added or deleted from Magento Catalog.')
            ]
        );

        if (!$this->isAdminStore()) {
            $form->addField(
                'website',
                'radio',
                [
                    'name' => 'auto_mode',
                    'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE,
                    'class' => 'admin__control-radio',
                    'after_element_html' => $this->__('Website') . '&nbsp;('.$this->getWebsiteName().')'
                ]
            );

            $form->addField(
                'note_website',
                'note',
                [
                    'text' => $this->__(
                        'Acts when a Product is added to or deleted from the Website with regard
                         to the Store View specified for the M2E Pro Listing.'
                    )
                ]
            );
        }

        $form->addField(
            'category',
            'radio',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY,
                'class' => 'admin__control-radio validate-one-required-by-name',
                'after_element_html' => $this->__('Category')
            ]
        );

        $form->addField(
            'note_category',
            'note',
            [
                'text' => $this->__(
                    'Acts when the Product is added to or deleted from the
                     selected Magento Category.'
                )
            ]
        );

        $form->addField(
            'validation',
            'text',
            [
                'class' => 'M2ePro-validate-mode',
                'style' => 'display: none;'
            ]
        );

        $this->css->add('label.mage-error[for="validation"] { width: 220px !important; }');

        return $form->toHtml();
    }

    //########################################
}
