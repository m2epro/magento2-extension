<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            $listing = $this->walmartFactory->getObjectLoaded('Listing', $id);
            if ($listing->isLocked()) {
                $locked++;
            } else {
                $listing->delete();
                $deleted++;
            }
        }

        $tempString = $this->__('%amount% Listing(s) were deleted', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString = $this->__(
            '%amount% Listing(s) have Listed Items and can not be deleted',
            $locked
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect($this->dataHelper->getBackUrl());
    }
}
