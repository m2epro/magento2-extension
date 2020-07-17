<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Prepare
 */
class Prepare extends Base
{
    //########################################

    public function execute()
    {
        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED);

        try {
            $this->migrationRunner->prepare();
        } catch (\Exception $exception) {
            $this->getMessageManager()->addErrorMessage(
                $this->__(
                    'Module was not prepared for migration. Reason: %error_message%.',
                    ['error_message' => $exception->getMessage()]
                )
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/disableModule'));
        }

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
    }

    //########################################
}
