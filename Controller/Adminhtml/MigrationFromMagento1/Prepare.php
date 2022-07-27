<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

class Prepare extends Base
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\Runner $migrationRunner
    ) {
        parent::__construct($context, $migrationRunner);

        $this->wizardHelper = $wizardHelper;
    }

    public function execute()
    {
        try {
            $this->migrationRunner->prepare();

            /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
            $wizard = $this->wizardHelper->getWizard(MigrationFromMagento1::NICK);
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
}
