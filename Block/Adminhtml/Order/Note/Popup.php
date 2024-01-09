<?php

/**
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
    /** @var \Ess\M2ePro\Model\Order\Note\Repository */
    private $noteRepository;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Model\Order\Note\Repository $noteRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->noteRepository = $noteRepository;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('order/note.phtml');
    }

    public function getNoteText()
    {
        $noteModel = $this->getNoteModel();
        return $noteModel ? $noteModel->getNote() : '';
    }

    public function getNoteModel()
    {
        if ($this->noteModel === null) {
            if ($noteId = $this->getRequest()->getParam('note_id')) {
                $this->noteModel = $this->noteRepository->get($noteId);
            }
        }

        return $this->noteModel;
    }
}
