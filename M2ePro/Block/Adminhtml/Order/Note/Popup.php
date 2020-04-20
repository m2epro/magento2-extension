<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Note;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Note\Grid
 */
class Popup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Order\Note */
    protected $noteModel;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('order/note.phtml');
    }

    //########################################

    public function getNoteModel()
    {
        if ($this->noteModel === null) {
            $this->noteModel = $this->activeRecordFactory->getObject('Order_Note');
            if ($noteId = $this->getRequest()->getParam('note_id')) {
                $this->noteModel->load($noteId);
            }
        }

        return $this->noteModel;
    }

    //########################################
}
