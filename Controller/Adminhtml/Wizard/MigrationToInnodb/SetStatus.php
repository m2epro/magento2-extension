<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb\SetStatus
 */
class SetStatus extends MigrationToInnodb
{
    public function execute()
    {
        return $this->setStatusAction();
    }
}
