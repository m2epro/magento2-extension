<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Shipping\Delete
 */
class Delete extends Template
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to remove.'));
            return $this->_redirect('*/amazon_template/index');
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $template */
            $template = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Template_Shipping',
                $id,
                null,
                false
            );

            if ($template === null) {
                continue;
            }

            if ($template->isLocked()) {
                $locked++;
            } else {
                $template->delete();
                $deleted++;
            }
        }

        $tempString = $this->__('%amount% record(s) were deleted.', $deleted);
        $deleted && $this->getMessageManager()->addSuccess($tempString);

        $tempString  = $this->__('%amount% record(s) are used in Listing(s).', $locked) . ' ';
        $tempString .= $this->__('Policy must not be in use to be deleted.');
        $locked && $this->getMessageManager()->addError($tempString);

        return $this->_redirect('*/amazon_template/index');
    }
}
