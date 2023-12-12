<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order\Note;

class Repository
{
    /** @var \Ess\M2ePro\Model\Order\NoteFactory */
    private $noteFactory;

    public function __construct(\Ess\M2ePro\Model\Order\NoteFactory $noteFactory)
    {
        $this->noteFactory = $noteFactory;
    }

    public function create(int $orderId, string $note): void
    {
            $noteModel = $this->noteFactory->create();
            $noteModel->setNote($note);
            $noteModel->setOrderId($orderId);
            $noteModel->save();
    }
}
