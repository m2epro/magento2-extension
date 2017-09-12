<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector\Tables\Get;

class Diff extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_NOTICE   = 'notice';

    const PROBLEM_TABLE_MISSING   = 'table_missing';
    const PROBLEM_TABLE_REDUNDANT = 'table_redundant';

    const PROBLEM_COLUMN_MISSING   = 'column_missing';
    const PROBLEM_COLUMN_REDUNDANT = 'column_redundant';
    const PROBLEM_COLUMN_DIFFERENT = 'column_different';

    // ########################################

    protected function getCommand()
    {
        return array('tables', 'get', 'diff');
    }

    protected function getRequestData()
    {
        return array(
            'tables_info' => $this->getHelper('Data')->jsonEncode(
                $this->getHelper('Module\Database\Structure')->getTablesInfo()
            )
        );
    }

    protected function validateResponse()
    {
        return true;
    }

    // ########################################
}