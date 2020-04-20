<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\DeleteNote
 */
class DeleteNote extends Order
{
    public function execute()
    {
        $noteId = $this->getRequest()->getParam('note_id');
        if ($noteId === null) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Order\Note $noteModel */
        $noteModel = $this->activeRecordFactory->getObjectLoaded('Order_Note', $noteId);
        $noteModel->delete();

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }
}
