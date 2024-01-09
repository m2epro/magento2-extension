<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order\Note;

class Repository
{
    /** @var \Ess\M2ePro\Model\Order\NoteFactory */
    private $noteFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Note\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Order\NoteFactory $noteFactory,
        \Ess\M2ePro\Model\ResourceModel\Order\Note\CollectionFactory $collectionFactory
    ) {
        $this->noteFactory = $noteFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(int $orderId, string $note): void
    {
        $noteModel = $this->noteFactory->create();
        $noteModel->setNote($note);
        $noteModel->setOrderId($orderId);
        $noteModel->save();
    }

    /**
     * @return \Ess\M2ePro\Model\Order\Note
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function get($id): \Ess\M2ePro\Model\Order\Note
    {
        return $this->noteFactory->create()->load($id);
    }

    public function save(\Ess\M2ePro\Model\Order\Note $note): void
    {
        $note->save();
    }

    public function delete(\Ess\M2ePro\Model\Order\Note $note): void
    {
        $note->delete();
    }

    public function deleteByOrderId(int $orderId): void
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);

        foreach ($collection as $note) {
            $this->delete($note);
        }
    }
}
