<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing\OpenAddProducts
 */
class OpenAddProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    public function execute()
    {
        $listingId   = $this->getRequest()->getParam('id');
        $accountId   = $this->getRequest()->getParam('account_id');
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if (!$account->getId()) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        if (empty($productsIds)) {
            $this->getMessageManager()->addError($this->__('Products not selected.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        $backUrl = $this->getUrl(
            '*/amazon_listing_product_repricing/addProducts',
            ['id' => $listingId, 'account_id' => $accountId]
        );

        /** @var $repricingAction \Ess\M2ePro\Model\Amazon\Repricing\Action\Product */
        $repricingAction = $this->modelFactory->getObject('Amazon_Repricing_Action_Product');
        $repricingAction->setAccount($account);
        $serverRequestToken = $repricingAction->sendAddProductsActionData($productsIds, $backUrl);

        if ($serverRequestToken === false) {
            $this->getMessageManager()->addError(
                $this->__('The selected Amazon Products cannot be Managed by Amazon Repricing Tool.')
            );
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        return $this->_redirect(
            $this->getHelper('Component_Amazon_Repricing')->prepareActionUrl(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_ADD,
                $serverRequestToken
            )
        );
    }
}
