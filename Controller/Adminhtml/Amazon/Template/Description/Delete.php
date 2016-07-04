<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class Delete extends Description
{
    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Item(s) to remove.'));
            $this->_redirect('*/*/index');
            return;
        }

        $deleted = $locked = 0;
        foreach ($ids as $id) {
            $template = $this->amazonFactory->getCachedObjectLoaded('Template\Description', $id);
            if ($template->isLocked()) {
                $locked++;
            } else {
                $template->delete();
                $deleted++;
            }
        }

        $tempString = $this->__('%deleted% record(s) were successfully deleted.', $deleted);
        $deleted && $this->messageManager->addSuccess($tempString);

        $tempString  = $this->__('%deleted% record(s) are used in Listing(s).', $locked) . ' ';
        $tempString .= $this->__('Policy must not be in use to be deleted.');
        $locked && $this->messageManager->addError($tempString);

        $this->_redirect('*/*/index');
    }

    //########################################
}