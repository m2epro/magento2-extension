<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Setup\MigrationFromMagento1\Runner;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Complete
 */
class Complete extends Base
{
    protected $migrationRunner;

    //########################################

    public function __construct(
        Context $context,
        Runner $migrationRunner
    ) {
        $this->migrationRunner = $migrationRunner;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        if ($this->getCurrentWizardStatus() === BaseMigrationFromMagento1::WIZARD_STATUS_COMPLETED) {
            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
        }

        if ($this->getCurrentWizardStatus() === BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS) {
            $this->getMessageManager()->addNoticeMessage(
                $this->__('Currently the database migration process is already running in the background.
                           So, its restarting is impossible. Please, wait for a while and press Continue button
                           again once the migration will be completed.')
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_IN_PROGRESS);

        try {
            $this->migrationRunner->run();
        } catch (\Exception $exception) {
            $this->getMessageManager()->addErrorMessage(
                $this->__('Migration is failed. Reason: %error_message%', $exception->getMessage())
            );
            $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_PREPARED);

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->getHelper('Module\Maintenance')->disable();

        $this->setWizardStatus(BaseMigrationFromMagento1::WIZARD_STATUS_COMPLETED);
        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
            \Ess\M2ePro\Model\Wizard\MigrationFromMagento1::NICK,
            'completed',
            1
        );

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
    }

    //########################################
}
