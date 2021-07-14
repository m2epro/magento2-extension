<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

/**
 * Class \Ess\M2ePro\Model\Wizard\MigrationFromMagento1
 */
class MigrationFromMagento1 extends Wizard
{
    const NICK = 'migrationFromMagento1';

    const STATUS_PREPARED            = 'prepared';
    const STATUS_UNEXPECTEDLY_COPIED = 'unexpectedly_copied_from_m1';
    const STATUS_IN_PROGRESS         = 'in_progress';
    const STATUS_COMPLETED           = 'completed';

    const STATUS_CONFIG_PATH         = 'm2epro/migrationFromMagento1/status';

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var string */
    protected $m1TablesPrefix;

    /** @var array */
    protected $steps = [
        'disableModule',
        'database',
        'congratulation'
    ];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    /**
     * @return string
     */
    public function getNick()
    {
        return self::NICK;
    }

    //########################################

    /**
     * @return string
     */
    public function getM1TablesPrefix()
    {
        if ($this->m1TablesPrefix === null) {
            $lastAddedTable = array_change_key_case(
                $this->resourceConnection->getConnection()->select()
                ->from('tables', ['TABLE_NAME'], 'information_schema')
                ->where('table_schema =?', $this->getHelper('Magento')->getDatabaseName())
                ->where('table_name like ?', '%m2epro_config')
                ->order('CREATE_TIME DESC')
                ->query()
                ->fetch()
            );
            $this->m1TablesPrefix = (string)preg_replace('/m2epro_[A-Za-z0-9_]+$/', '', $lastAddedTable['table_name']);
        }

        return $this->m1TablesPrefix;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCurrentStatus()
    {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                $this->helperFactory->getObject('Module_Database_Structure')
                    ->getTableNameWithPrefix('core_config_data'),
                'value'
            )
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0)
            ->where('path = ?', self::STATUS_CONFIG_PATH);

        return $this->resourceConnection->getConnection()->fetchOne($select);
    }

    /**
     * @param $status
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setCurrentStatus($status)
    {
        if ($this->getCurrentStatus() === false) {
            $this->resourceConnection->getConnection()->insert(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                [
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => self::STATUS_CONFIG_PATH,
                    'value'    => $status
                ]
            );
        } else {
            $this->resourceConnection->getConnection()->update(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('core_config_data'),
                ['value' => $status],
                [
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => self::STATUS_CONFIG_PATH,
                ]
            );
        }
    }

    /**
     * @return string
     */
    public function getPossibleM1Domain()
    {
        $configTable = $this->getM1TablesPrefix() . 'm2epro_config';
        if (!$this->resourceConnection->getConnection()->isTableExists($configTable)) {
            return '';
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($configTable, 'value')
            ->where('`group` = ?', '/location/')
            ->where('`key` = ?', 'domain');

        $possibleDomain = $this->resourceConnection->getConnection()->fetchOne($select);
        return $possibleDomain ? trim($possibleDomain, '/') : '';
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isUnexpectedlyCopiedFromM1()
    {
        if (!$this->getHelper('Module')->isProductionEnvironment()) {
            return false;
        }

        $installedVersion = $this->getHelper('Module')->getDataVersion();

        $setupTable = $this->getM1TablesPrefix() . 'm2epro_setup';
        if (!$this->resourceConnection->getConnection()->isTableExists($setupTable)) {
            return false;
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($setupTable, 'version_to')
            ->order('id DESC')
            ->limit(1);

        $lastUpgradeVersion = $this->resourceConnection->getConnection()->fetchOne($select);

        /**
         * Hope m2 version will never be larger than m1 one
         */
        return version_compare($lastUpgradeVersion, $installedVersion, '>');
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isStarted()
    {
        return in_array(
            $this->getCurrentStatus(),
            [
                self::STATUS_IN_PROGRESS,
                self::STATUS_PREPARED,
                self::STATUS_UNEXPECTEDLY_COPIED,
            ],
            true
        );
    }

    //########################################
}
