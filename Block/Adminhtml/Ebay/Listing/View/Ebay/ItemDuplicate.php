<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\ItemDuplicate
 */
class ItemDuplicate extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingViewEbayItemDuplicate');
    }

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    protected function getItemId()
    {
        $duplicateMark = $this->listingProduct->getSetting('additional_data', 'item_duplicate_action_required');

        return isset($duplicateMark['item_id']) ? $duplicateMark['item_id'] : null;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'ebay_listing_item_duplicate_fieldset',
            [
                'legend' => '',
            ]
        );

        $itemId = $this->getItemId();

        $fieldset->addField(
            'ebay_listing_item_duplicate_ignore',
            'link',
            [
                'label' => '',
                'href' => 'javascript://',
                'onclick' => "EbayListingViewEbayGridObj.solveItemDuplicateAction
                    ({$this->listingProduct->getId()}, false, false)",
                'value' => __('Ignore Duplicate Item Alert for Item %1', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top',
                'after_element_html' => __(' - M2E Pro will run another attempt to List/Relist the Product on eBay
                                                    to check if the issue persists<br><br>'),
            ]
        );

        $fieldset->addField(
            'ebay_listing_item_duplicate_stop',
            'link',
            [
                'label' => '',
                'href' => 'javascript://',
                'onclick' => "EbayListingViewEbayGridObj.solveItemDuplicateAction
                    ({$this->listingProduct->getId()}, true, false)",
                'value' => __('Stop Item %1 on eBay', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top',
                'after_element_html' => __(' - it will automatically stop Item 266377387238 on eBay and prevent
                                                    Duplicate Item Alert during the next attempt to List/Relist the Product<br><br>'),
            ]
        );

        $fieldset->addField(
            'ebay_listing_item_duplicate_stop_and_list',
            'link',
            [
                'label' => '',
                'href' => 'javascript://',
                'onclick' => "EbayListingViewEbayGridObj.solveItemDuplicateAction
                    ({$this->listingProduct->getId()}, true, true)",
                'value' => __('Stop Item %1 on eBay and List/Relist your Product from M2E Pro Listing', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top',
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $itemId = $this->getItemId();

        $url = $this->getUrl(
            '*/ebay_listing/gotoEbay/',
            [
                'item_id' => $itemId,
                'account_id' => $this->listingProduct->getAccount()->getId(),
                'marketplace_id' => $this->listingProduct->getMarketplace()->getId(),
            ]
        );

        $linkHtml = sprintf(
            '<a class="external-link" target="_blank" href="%s">%s</a>',
            $url,
            $itemId
        );

        return __(
            '<div><p>When attempting to list/relist an item on eBay, M2E Pro detected that the item is a duplicate of
                another item already listed on the Channel (ID %1).</p>

            <p>According to the eBay <a class="external-link" target="_blank" href="http://pages.ebay.com/help/policies/listing-multi.html">
            Duplicate Listings Policy</a>, you are not allowed to list the same item multiple times.
             Choose from the solutions below to resolve this issue and keep your eBay listings in compliance:</p>
           </div>',
            $linkHtml
        ) . parent::_toHtml();
    }
}
