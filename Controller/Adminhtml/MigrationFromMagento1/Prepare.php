<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Prepare
 */
class Prepare extends Base
{
    //########################################

    public function execute()
    {
        try {
            $this->migrationRunner->prepare();

            /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
            $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
            $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_PREPARED);
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
