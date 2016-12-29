<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class ItemDuplicate extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewEbayItemDuplicate');
        // ---------------------------------------
    }

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
    }

    // ---------------------------------------

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

        if ($itemId) {
            $url = $this->getUrl(
                '*/ebay_listing/gotoEbay/',
                array(
                    'item_id' => $itemId,
                    'account_id' => $this->listingProduct->getAccount()->getId(),
                    'marketplace_id' => $this->listingProduct->getMarketplace()->getId()
                )
            );

            $fieldset->addField(
                'ebay_listing_item_duplicate_ebay_link',
                'link',
                [
                    'label' => $this->__('View this item on eBay'),
                    'href' => $url,
                    'target' => '_blank',
                    'value' => $itemId,
                    'class' => 'external-link',
                    'style' => 'position: relative; top: 6px;'
                ]
            );
        }

        $fieldset->addField(
            'ebay_listing_item_duplicate_ignore',
            'link',
            [
                'label' => '',
                'href' => 'javascript://',
                'onclick' => "EbayListingViewEbayGridObj.solveItemDuplicateAction
                    ({$this->listingProduct->getId()}, false, false)",
                'value' => $this->__('Ignore this problem for the Item %s%', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top'
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
                'value' => $this->__('Stop Item %s% on eBay', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top'
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
                'value' => $this->__('Stop Item %s% on eBay and List/Relist the current Item', $itemId),
                'css_class' => 'no-margin-bottom no-margin-top'
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();

    }

    protected function _toHtml()
    {
        return $this->__(
            '<div><p>During the last action running (list/relist action) there was a notification returned from
             eBay that the Item you are trying to update is a duplicate <br> of the already presented Item %s%.</p>

            <p>It might be caused by several possible reasons:</p>
            <ul style="margin-left: 30px;">
                <li>Temporary network-related issues;</li>
                <li>Restoring of the database from the backup, etc.</li>
            </ul>

            <p>The duplicated Items are not allowed to be presented on eBay according to the eBay
            <a class="external-link" target="_blank" href="http://pages.ebay.com/help/policies/listing-multi.html">
            Duplicate Listings Policy</a> terms.
            That is why, you should apply one of the solutions provided below to solve this issue:</p>
            <ul style="margin-left: 30px;">
                <li>Ignore this message for the Item %s% <br>
                It means that during the next attempt to list/relist your Item, the data will be sent to eBay
                to check whether the issue is still persist.</li>
                <li>Stop Item %s% on eBay <br>
                It means that the Item will be automatically Stopped on eBay and you will be able to list your current
                Item without the further issues.</li>
                <li>Stop Item %s% on eBay and list/relist the current Item <br>
                It means that the Item will be automatically Stopped on eBay and the new one
                will be listed/relisted.</li>
            </ul></div>',
            $this->getItemId()
        ) . parent::_toHtml();
    }

    //########################################
}