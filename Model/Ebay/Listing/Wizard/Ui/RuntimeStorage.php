<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager;

class RuntimeStorage
{
    private Manager $manager;

    public function setManager(Manager $manager): void
    {
        $this->manager = $manager;
    }

    public function getManager(): Manager
    {
        if (!isset($this->manager)) {
            throw new \LogicException('Listing wizard manager has not been set.');
        }

        return $this->manager;
    }
}
