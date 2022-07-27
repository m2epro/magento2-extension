<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

class InitUnexpectedlyCopied extends Base
{
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $moduleMaintenanceHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Maintenance $moduleMaintenanceHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Setup\MigrationFromMagento1\Runner $migrationRunner
    ) {
        parent::__construct($context, $migrationRunner);

        $this->moduleMaintenanceHelper = $moduleMaintenanceHelper;
    }

    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        $wizard->setCurrentStatus(MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED);

        $this->moduleMaintenanceHelper->enable();

        return $this->_redirect($this->getUrl('m2epro/wizard_migrationFromMagento1/database'));
    }
}
