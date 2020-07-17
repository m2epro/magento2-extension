<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb\RunSynchNow
 */
class MarketplacesSynchronization extends MigrationToInnodb
{
    //########################################

    public function execute()
    {
        $this->addContent(
            $this->createBlock($this->nameBuilder->buildClassName([
                'Wizard', 'MigrationToInnodb_Installation_MarketplacesSynchronization'
            ]))->setData([
                'nick' => $this->getNick()
            ])
        );
        return $this->getResult();
    }

    //########################################
}
