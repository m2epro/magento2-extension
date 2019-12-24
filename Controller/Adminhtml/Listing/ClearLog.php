<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\ClearLog
 */
class ClearLog extends Listing
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->getMessageManager()->addError($this->__('Please select Item(s) to clear.'));
            $this->_redirect('*/*/index');
            return;
        }

        foreach ($ids as $id) {
            $this->activeRecordFactory->getObject('Listing\Log')->clearMessages($id);
        }

        $this->getMessageManager()->addSuccess($this->__('The Listing(s) Log was successfully cleared.'));
        $this->_redirect($this->getHelper('Data')->getBackUrl('list'));
    }
}
