<?php

namespace Ess\M2ePro\Model\Tag;

class EntityFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $text, string $errorCode, \DateTime $createDate): Entity
    {
        /** @var Entity $entity */
        $entity = $this->objectManager->create(Entity::class);
        $entity->setText($text);
        $entity->setErrorCode($errorCode);
        $entity->setCreateDate($createDate);

        return $entity;
    }
}
