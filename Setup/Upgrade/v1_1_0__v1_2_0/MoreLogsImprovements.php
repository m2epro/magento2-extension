<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class MoreLogsImprovements extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/logs/view/grouped/', 'max_last_handled_records_count', 100000
        );

        //----------------------------------------

        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            ['value' => 90],
            new \Zend_Db_Expr('`group` LIKE "/logs/clearing/%" AND `key` = "days" AND `value` > 90')
        );
    }

    //########################################
}