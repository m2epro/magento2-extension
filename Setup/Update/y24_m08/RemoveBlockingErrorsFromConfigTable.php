<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m08;

class RemoveBlockingErrorsFromConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConfigModifier('module')->delete('/blocking_errors/ebay/', 'retry_seconds');
        $this->getConfigModifier('module')->delete('/blocking_errors/ebay/', 'errors_list');
    }
}
