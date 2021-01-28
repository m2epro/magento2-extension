<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Create;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Create\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    protected $sessionKey = 'walmart_listing_create';

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $listing = $this->createListing();

            if ($this->isCreationModeListingOnly()) {
                // closing window for Unmanaged products moving in new listing creation

                return $this->getRawResult()->setContents("<script>window.close();</script>");
            }

            return $this->_redirect(
                '*/walmart_listing_product_add/index',
                [
                    'id' => $listing->getId(),
                    'new_listing' => 1
                ]
            );
        }

        $content = $this->createBlock('Walmart_Listing_Create');
        $this->addContent($content);

        $this->setPageHelpLink('x/NQBhAQ');
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('New Listing Creation'));

        return $this->getResult();
    }

    //########################################

    protected function createListing()
    {
        $post = $this->getRequest()->getParams();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObjectLoaded(
            'Account',
            (int)$post['account_id']
        );

        $post['marketplace_id'] = $account->getChildObject()->getMarketplaceId();

        $listing = $this->walmartFactory->getObject('Listing')
            ->addData($post)
            ->save();

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($listing->getComponentMode());
        $tempLog->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_LISTING,
            'Listing was Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
        );

        return $listing;
    }

    //########################################

    private function isCreationModeListingOnly()
    {
        return $this->getRequest()->getParam('creation_mode') ==
            \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY;
    }

    //########################################
}
