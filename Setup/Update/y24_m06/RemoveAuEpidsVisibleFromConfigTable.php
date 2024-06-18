<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m06;

class RemoveAuEpidsVisibleFromConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConfigModifier('module')
             ->delete('/ebay/configuration/', 'au_epids_visible');
    }
}
