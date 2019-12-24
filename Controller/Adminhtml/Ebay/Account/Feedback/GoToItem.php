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
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_id');

        if ($feedbackId === null) {
            $this->getMessageManager()->addError($this->__('Feedback is not defined.'));
            return $this->_redirect('*/ebay_account/index');
        }

        /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId);
        $itemId = $feedback->getData('ebay_item_id');

        $listingProduct = $this->getHelper('Component\Ebay')->getListingProductByEbayItem(
            $feedback->getData('ebay_item_id'),
            $feedback->getData('account_id')
        );

        if ($listingProduct !== null) {
            $itemUrl = $this->getHelper('Component\Ebay')->getItemUrl(
                $itemId,
                $listingProduct->getListing()->getAccount()->getChildObject()->getMode(),
                $listingProduct->getListing()->getMarketplaceId()
            );

            return $this->_redirect($itemUrl);
        }

        $order = $feedback->getOrder();

        if ($order !== null && $order->getMarketplaceId() !== null) {
            $itemUrl = $this->getHelper('Component\Ebay')->getItemUrl(
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
