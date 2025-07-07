<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Template;

interface CategoryInterface
{
    public function getId();

    public function getIsCustomTemplate(): int;

    public function getCategoryPath(): string;

    public function getCategoryMode(): int;

    /**
     * @return int|string
     */
    public function getCategoryValue();
}
