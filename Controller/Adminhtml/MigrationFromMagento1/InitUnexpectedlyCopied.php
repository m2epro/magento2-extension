<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\InitUnexpectedlyCopied
 */
class InitUnexpectedlyCopied extends Base
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED);

        $this->getHelper('Module\Maintenance')->enable();

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
    }

    //########################################
}
