<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class Delete extends Listing
{
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
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing',$id);
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
            '%amount% Listing(s) cannot be deleted because they have Items with Status "In Progress".', $locked
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect('*/ebay_listing/index');
    }
}