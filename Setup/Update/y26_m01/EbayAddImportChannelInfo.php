<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m01;

use Ess\M2ePro\Helper\Module\Database\Tables;

class EbayAddImportChannelInfo extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/ebay/configuration/', 'import_channel_info', '0');
    }
}
