<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class DeleteNote extends Order
{
    /** @var \Ess\M2ePro\Model\Order\Note\Repository */
    private $noteRepository;

    public function __construct(
        \Ess\M2ePro\Model\Order\Note\Repository $noteRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->noteRepository = $noteRepository;
    }

    public function execute()
    {
        $noteId = $this->getRequest()->getParam('note_id');
        if ($noteId === null) {
            $this->setJsonContent(['result' => false]);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Order\Note $noteModel */
        $noteModel = $this->noteRepository->get($noteId);
        $this->noteRepository->delete($noteModel);

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }
}
