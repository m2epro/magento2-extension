<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\SaveNote
 */
class SaveNote extends Order
{
    public function execute()
    {
        $noteText = $this->getRequest()->getParam('note');
        if ($noteText === null) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Order\Note $noteModel */
        $noteModel = $this->activeRecordFactory->getObject('Order_Note');
        if ($noteId = $this->getRequest()->getParam('note_id')) {
            $noteModel->load($noteId);
            $noteModel->setData('note', $noteText);
        } else {
            $noteModel->setData('note', $noteText);
            $noteModel->setData('order_id', $this->getRequest()->getParam('order_id'));
        }

        $noteModel->save();

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }
}
