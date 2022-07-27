<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

class MarketplacesSynchronization extends MigrationToInnodb
{
    public function execute()
    {
        $this->addContent(
            $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb\Installation\MarketplacesSynchronization::class
            )->setData([
                'nick' => $this->getNick()
            ])
        );
        return $this->getResult();
    }
}
