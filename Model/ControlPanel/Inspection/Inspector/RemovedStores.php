<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class RemovedStores implements InspectorInterface, FixerInterface
{
    /** @var array */
    private $removedStoresId = [];

    /** @var StoreManager */
    private $storeManager;

    /** @var HelperFactory */
    private $helperFactory;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var IssueFactory */
    private $issueFactory;

    public function __construct(
        HelperFactory $helperFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        StoreManager $storeManager,
        IssueFactory $issueFactory
    ) {
        $this->helperFactory      = $helperFactory;
        $this->urlBuilder         = $urlBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager       = $storeManager;
        $this->issueFactory       = $issueFactory;
    }

    //########################################

    private function getRemovedStores()
    {
        $existsStoreIds = array_keys($this->storeManager->getStores(true));
        $storeRelatedColumns = $this->helperFactory->getObject('Module_Database_Structure')->getStoreRelatedColumns();

        $usedStoresIds = [];

        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {
                $tempResult = $this->resourceConnection->getConnection()->select()
                    ->distinct()
                    ->from(
                        $this->helperFactory
                            ->getObject('Module_Database_Structure')
                            ->getTableNameWithPrefix($tableName),
                        [$columnInfo['name']]
                    )
                    ->where("{$columnInfo['name']} IS NOT NULL")
                    ->query()
                    ->fetchAll(\Zend_Db::FETCH_COLUMN);

                if ($columnInfo['type'] == 'int') {
                    $usedStoresIds = array_merge($usedStoresIds, $tempResult);
                    continue;
                }

                // json
                foreach ($tempResult as $itemRow) {
                    preg_match_all('/"(store|related_store)_id":"?([\d]+)"?/', $itemRow, $matches);
                    !empty($matches[2]) && $usedStoresIds = array_merge($usedStoresIds, $matches[2]);
                }
            }
        }

        $usedStoresIds = array_values(array_unique(array_map('intval', $usedStoresIds)));
        $this->removedStoresId = array_diff($usedStoresIds, $existsStoreIds);
    }
    //########################################

    public function process()
    {
        $issues =[];
        $this->getRemovedStores();

        if (!empty($this->removedStoresId)) {
            $issues[] = $this->issueFactory->create(
                'Some data have nonexistent magento stores',
                $this->renderMetadata($this->removedStoresId)
            );
        }

        return $issues;
    }

    private function renderMetadata($data)
    {
        $removedStoreIds = implode(', ', $data);
        $repairStoresAction = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'repairRemovedMagentoStore']);

        $html = <<<HTML
<div style="margin:0 0 10px">Removed Store IDs: {$removedStoreIds}</div>
<form action="{$repairStoresAction}" method="get">
    <input name="replace_from" value="" type="text" placeholder="replace from id" required/>
    <input name="replace_to" value="" type="text" placeholder="replace to id" required />
    <button type="submit">Repair</button>
</form>
HTML;
        return $html;
    }

    public function fix($ids)
    {
        foreach ($ids as $replaceIdFrom => $replaceIdTo) {
            $this->replaceId($replaceIdFrom, $replaceIdTo);
        }
    }

    private function replaceId($replaceIdFrom, $replaceIdTo)
    {
        $storeRelatedColumns = $this->helperFactory->getObject('Module_Database_Structure')->getStoreRelatedColumns();
        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {
                if ($columnInfo['type'] == 'int') {
                    $this->resourceConnection->getConnection()->update(
                        $this->helperFactory
                            ->getObject('Module_Database_Structure')
                            ->getTableNameWithPrefix($tableName),
                        [$columnInfo['name'] => $replaceIdTo],
                        "`{$columnInfo['name']}` = {$replaceIdFrom}"
                    );

                    continue;
                }

                // json ("store_id":"10" | "store_id":10, | "store_id":10})
                $bind = [$columnInfo['name'] => new \Zend_Db_Expr(
                    "REPLACE(
                        REPLACE(
                            REPLACE(
                                `{$columnInfo['name']}`,
                                'store_id\":{$replaceIdFrom},',
                                'store_id\":{$replaceIdTo},'
                            ),
                            'store_id\":\"{$replaceIdFrom}\"',
                            'store_id\":\"{$replaceIdTo}\"'
                        ),
                        'store_id\":{$replaceIdFrom}}',
                        'store_id\":{$replaceIdTo}}'
                    )"
                )];

                $this->resourceConnection->getConnection()->update(
                    $this->helperFactory->getObject('Module_Database_Structure')->getTableNameWithPrefix($tableName),
                    $bind,
                    "`{$columnInfo['name']}` LIKE '%store_id\":\"{$replaceIdFrom}\"%' OR
                     `{$columnInfo['name']}` LIKE '%store_id\":{$replaceIdFrom},%' OR
                     `{$columnInfo['name']}` LIKE '%store_id\":{$replaceIdFrom}}%'"
                );
            }
        }
    }

    //########################################
}
