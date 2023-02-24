<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Unmanaged;

class Reset extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other */
    private $listingOtherResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other $listingOtherResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->listingOtherResource = $listingOtherResource;
    }

    public function execute()
    {
        $this->listingOtherResource->resetEntities();

        $this->messageManager->addSuccessMessage(
            __('eBay Unmanaged Listings were reset.')
        );

        $this->_redirect($this->redirect->getRefererUrl());
    }
}
