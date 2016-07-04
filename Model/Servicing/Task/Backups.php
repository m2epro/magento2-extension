<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Backups extends \Ess\M2ePro\Model\Servicing\Task
{
    const MAX_ALLOWED_ITEMS_PER_REQUEST = 10000;

    /** @var \Ess\M2ePro\Model\Servicing\Task\Backups\Manager */
    private $backup = null;

    //########################################

    public function __construct(
        \Magento\Eav\Model\Config $config,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    )
    {
        parent::__construct(
            $config,
            $cacheConfig,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );

        $this->backup = $this->modelFactory->getCachedObject('Servicing\Task\Backups\Manager');
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'backups';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $requestData = array('tables' => array());

        $totalItems = 0;

        foreach ($this->getHelper('Module\Database\Structure')->getMySqlTables() as $tableName) {
            if (!$this->backup->canBackupTable($tableName) || !$this->backup->isTimeToBackupTable($tableName)) {
                continue;
            }

            $dump = $this->backup->getTableDump($tableName);
            $requestData['tables'][$tableName] = $dump;

            $this->backup->updateTableLastAccessDate($tableName);

            $totalItems += count($dump);

            if ($totalItems >= self::MAX_ALLOWED_ITEMS_PER_REQUEST) {
                break;
            }
        }

        return $requestData;
    }

    public function processResponseData(array $data)
    {
        $this->backup->deleteSettings();

        if (isset($data['settings']) && is_array($data['settings'])) {
            $this->backup->setSettings($data['settings']);
        }
    }

    //########################################
}