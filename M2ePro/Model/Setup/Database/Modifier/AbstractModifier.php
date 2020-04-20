<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Database\Modifier;

use Ess\M2ePro\Model\AbstractModel;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\Setup;

/**
 * Class \Ess\M2ePro\Model\Setup\Database\Modifier\AbstractModifier
 */
class AbstractModifier extends AbstractModel
{
    /** @var Setup */
    protected $installer;

    /** @var AdapterInterface */
    protected $connection;

    protected $tableName = null;
    protected $queriesLog = [];

    //########################################

    public function __construct(
        Setup $installer,
        $tableName,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->installer  = $installer;
        $this->connection = $installer->getConnection();

        $this->helperFactory = $helperFactory;

        if (!$this->getHelper('Module_Database_Tables')->isExists($tableName)) {
            throw new \Ess\M2ePro\Model\Exception\Setup("Table \"{$tableName}\" does not exist.");
        }

        $this->tableName = $this->getHelper('Module_Database_Tables')->getFullName($tableName);

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function runQuery($query)
    {
        $this->addQueryToLog($query);

        $this->connection->query($query);
        $this->connection->resetDdlCache();

        return $this;
    }

    public function addQueryToLog($query)
    {
        $this->queriesLog[] = $query;
        return $this;
    }

    // ---------------------------------------

    public function setQueriesLog(array $queriesLog = [])
    {
        $this->queriesLog = $queriesLog;
        return $this;
    }

    public function getQueriesLog()
    {
        return $this->queriesLog;
    }

    //########################################
}
