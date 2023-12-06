<?php

namespace Ess\M2ePro\Model\Order;

class NoteFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Note
    {
        return $this->objectManager->create(Note::class, $data);
    }
}
