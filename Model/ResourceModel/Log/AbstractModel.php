<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Log;

abstract class AbstractModel extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    private const ACTION_KEY = 'last_action_id';

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
        $this->dbStructureHelper = $dbStructureHelper;
    }

    public function getConfigGroupSuffix(): string
    {
        return 'general';
    }

    public function getNextActionId(): int
    {
        $connection = $this->getConnection();

        $table = $this->dbStructureHelper->getTableNameWithPrefix('m2epro_config');
        $groupConfig = '/logs/' . $this->getConfigGroupSuffix() . '/';

        $lastActionId = (int)$connection->select()
                                        ->from($table, 'value')
                                        ->where('`group` = ?', $groupConfig)
                                        ->where('`key` = ?', self::ACTION_KEY)
                                        ->query()->fetchColumn();

        $nextActionId = $lastActionId + 1;

        $connection->update(
            $table,
            ['value' => $nextActionId],
            ['`group` = ?' => $groupConfig, '`key` = ?' => 'last_action_id']
        );

        return $nextActionId;
    }

    public function clearMessages($filters = []): void
    {
        $where = [];
        foreach ($filters as $column => $value) {
            $where[$column . ' = ?'] = $value;
        }

        $this->getConnection()->delete($this->getMainTable(), $where);
    }
}
