<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\GoToItem
 */
class GoToItem extends Account
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $helperEbay;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $helperEbay,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperEbay = $helperEbay;
    }

    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_id');

        if ($feedbackId === null) {
            $this->getMessageManager()->addError($this->__('Feedback is not defined.'));
            return $this->_redirect('*/ebay_account/index');
        }

        /** @var \Ess\M2ePro\Model\Ebay\Feedback $feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId);
        $itemId = $feedback->getData('ebay_item_id');

        $listingProduct = $this->helperEbay->getListingProductByEbayItem(
            $feedback->getData('ebay_item_id'),
            $feedback->getData('account_id')
        );

        if ($listingProduct !== null) {
            $itemUrl = $this->helperEbay->getItemUrl(
                $itemId,
                $listingProduct->getListing()->getAccount()->getChildObject()->getMode(),
                $listingProduct->getListing()->getMarketplaceId()
            );

            return $this->_redirect($itemUrl);
        }

        $order = $feedback->getOrder();

        if ($order !== null && $order->getMarketplaceId() !== null) {
            $itemUrl = $this->helperEbay->getItemUrl(
                $itemId,
                $order->getAccount()->getChildObject()->getMode(),
                $order->getMarketplaceId()
            );

            return $this->_redirect($itemUrl);
        }

        $this->getMessageManager()->addError($this->__('Item\'s Site is Unknown.'));

        return $this->_redirect('*/ebay_account/index');
    }
}
