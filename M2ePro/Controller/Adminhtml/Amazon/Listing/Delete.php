<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Delete
 */
class Delete extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    //########################################

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
            $listing = $this->amazonFactory->getObjectLoaded('Listing', $id);
            if ($listing->isLocked()) {
                $locked++;
            } else {
                $listing->delete();
                $deleted++;
            }
        }

        $tempString = $this->__('%amount% Listing(s) were successfully deleted', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString = $this->__(
            '%amount% Listing(s) have Listed Items and can not be deleted',
            $locked
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect($this->getHelper('Data')->getBackUrl());
    }

    //########################################
}
