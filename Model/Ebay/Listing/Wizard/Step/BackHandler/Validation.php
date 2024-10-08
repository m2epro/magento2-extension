<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Step\BackHandler;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Step\BackHandlerInterface;

class Validation implements BackHandlerInterface
{
    public function process(Manager $manager): void
    {
        $manager->clearValidationData();
    }
}
