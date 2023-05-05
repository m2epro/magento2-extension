<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m03;

class AddWizardVersionDowngrade extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute(): void
    {
        $this->getConnection()->insert(
            $this->getFullTableName('wizard'),
            [
                'nick' => 'versionDowngrade',
                'view' => '*',
                'status' => 3,
                'step' => null,
                'type' => 1,
                'priority' => 7,
            ]
        );
    }
}
