<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class CanCreateResultMessage
{
    public int $type;
    public string $description;
    public array $params;
    public array $links;
    public bool $isUnique;
    public array $additionalData;

    public function __construct(
        int $type,
        string $description,
        array $params = [],
        array $links = [],
        bool $isUnique = false,
        array $additionalData = []
    ) {
        $this->type = $type;
        $this->description = $description;
        $this->params = $params;
        $this->links = $links;
        $this->isUnique = $isUnique;
        $this->additionalData = $additionalData;
    }
}
