<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\Step;

interface BackHandlerInterface
{
    public function process(\Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager $manager): void;
}
