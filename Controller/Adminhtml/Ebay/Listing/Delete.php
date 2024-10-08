<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\DeleteService;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Delete extends Listing
{
    private DeleteService $deleteService;

    public function __construct(
        DeleteService $deleteService,
        Factory $ebayFactory,
        Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->deleteService = $deleteService;
    }

    public function execute()
    {
        $ids = $this->getRequestIds();
        $backUrl = '*/ebay_listing/index';

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to remove.'));
            $this->_redirect($backUrl);

            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);
            if ($listing->isLocked()) {
                $locked++;
            } else {
                $listing->delete();
                $this->deleteService->removeByListing($listing);
                $deleted++;
            }
        }

        $tempString = $this->__('%amount% Listing(s) were deleted', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString = $this->__(
            '%amount% Listing(s) cannot be deleted because they have Items with Status "In Progress".',
            $locked
        );
        $locked && $this->getMessageManager()->addError($tempString);

        $this->_redirect($backUrl);
    }
}
