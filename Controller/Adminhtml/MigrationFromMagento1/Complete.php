<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Complete
 */
class Complete extends Base
{
    const MIGRATION_MESSAGE_IDENTIFIER = 'm2epro_migration_message';

    /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\Factory */
    protected $preconditionsCheckerFactory;

    /** @var \Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader */
    protected $tablesDownloader;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\Runner $migrationRunner,
        \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\Factory $preconditionsCheckerFactory,
        \Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader $mappingTablesDownloader
    ) {
        parent::__construct($context, $migrationRunner);

        $this->preconditionsCheckerFactory = $preconditionsCheckerFactory;
        $this->tablesDownloader            = $mappingTablesDownloader;
    }

    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);

        $currentWizardStatus = $wizard->getCurrentStatus();

        if ($currentWizardStatus === MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED &&
            !$this->tablesDownloader->isDownloadComplete()
        ) {
            try {
                $m1Url = $this->getRequest()->getParam('magento_1_url')
                    ? $this->tablesDownloader->resolveM1Endpoint($this->getRequest()->getParam('magento_1_url'))
                    : $this->getHelper('Data\Session')->getValue('unexpected_migration_m1_url', true);

                if ($m1Url !== null) {
                    $this->tablesDownloader->setM1BaseUrl($m1Url);
                    $this->tablesDownloader->setIsNeedToDisableM1($this->getRequest()->getParam('disable_m1_module'));
                    $this->tablesDownloader->download();
                }
            } catch (\Exception $exception) {

                $this->getHelper('Module_Exception')->process($exception);
                $this->getMessageManager()->addErrorMessage($this->__($exception->getMessage()));

                $message = $this->getMessageManager()->getMessages()->getLastAddedMessage();
                $message->setIdentifier('m2epro_message_identifier');

                return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
            }
        }

        if ($currentWizardStatus === MigrationFromMagento1::STATUS_COMPLETED) {
            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
        }

        if ($currentWizardStatus === MigrationFromMagento1::STATUS_IN_PROGRESS) {
            $this->getMessageManager()->addNoticeMessage(
                $this->__(
                    'Currently the database migration process is already running in the background.
                    So, its restarting is impossible. Please, wait for a while and press Continue button
                    again once the migration will be completed.'
                )
            );

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_IN_PROGRESS);

        try {
            $preconditionsChecker = $this->preconditionsCheckerFactory->create($currentWizardStatus);
            $this->migrationRunner->setPreconditionsChecker($preconditionsChecker);
            $this->migrationRunner->run();
        } catch (\Exception $exception) {
            $this->getHelper('Module_Exception')->process($exception);
            $this->getMessageManager()->addErrorMessage(
                $this->__('Migration is failed. Reason: %error_message%', $exception->getMessage())
            );

            $message = $this->getMessageManager()->getMessages()->getLastAddedMessage();
            $message->setIdentifier(self::MIGRATION_MESSAGE_IDENTIFIER);

            $wizard->setCurrentStatus($currentWizardStatus);

            return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
        }

        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue('/cron/', 'mode', 0);
        $this->getHelper('Module\Maintenance')->disable();

        $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_COMPLETED);
        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
            MigrationFromMagento1::NICK,
            'completed',
            1
        );

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/installation'));
    }

    //########################################
}
