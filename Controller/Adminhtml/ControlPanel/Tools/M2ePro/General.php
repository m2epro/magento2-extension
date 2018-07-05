<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Tools\M2ePro;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;
use Ess\M2ePro\Model\Exception\Connection;
use Ess\M2ePro\Model\Exception\Logic;

class General extends Command
{
    private $storeManager;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Clear Cache"
     * @description "Clear extension cache"
     * @confirm "Are you sure?"
     */
    public function clearExtensionCacheAction()
    {
        $this->getHelper('Module')->clearCache();
        $this->getMessageManager()->addSuccess('Extension cache was successfully cleared.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
    }

    /**
     * @title "Clear Config Cache"
     * @description "Clear config cache"
     * @confirm "Are you sure?"
     */
    public function clearConfigCacheAction()
    {
        $this->getHelper('Module')->clearConfigCache();
        $this->getMessageManager()->addSuccess('Config cache was successfully cleared.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
    }

    /**
     * @title "Clear Variables Dir"
     * @description "Clear Variables Dir"
     * @confirm "Are you sure?"
     * @new_line
     */
    public function clearVariablesDirAction()
    {
        $this->modelFactory->getObject('VariablesDir')->removeBaseForce();
        $this->getMessageManager()->addSuccess('Variables dir was successfully cleared.');
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
    }

    //########################################

    /**
     * @title "Repair Broken Tables"
     * @description "Command for show and repair broken horizontal tables"
     */
    public function checkTablesAction()
    {
        $tableNames = $this->getRequest()->getParam('table', array());

        if (!empty($tableNames)) {
            $this->getHelper('Module\Database\Repair')->repairBrokenTables($tableNames);
            return $this->_redirect($this->getUrl('*/*/*', ['action' => 'checkTables']));
        }

        $brokenTables = $this->getHelper('Module\Database\Repair')->getBrokenTablesInfo();

        if ($brokenTables['total_count'] <= 0) {
            return $this->getEmptyResultsHtml('No Broken Tables');
        }

        $currentUrl = $this->getUrl('*/*/*', ['action' => 'checkTables']);
        $infoUrl = $this->getUrl('*/*/*', ['action' => 'showBrokenTableIds']);

        $html = <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Broken Tables
            <span style="color: #808080; font-size: 15px;">({$brokenTables['total_count']} entries)</span>
        </h2>
        <br/>
        <form method="GET" action="{$currentUrl}">
            <input type="hidden" name="action" value="repair" />
            <table class="grid" cellpadding="0" cellspacing="0">
HTML;
        if (count($brokenTables['parent'])) {

            $html .= <<<HTML
<tr bgcolor="#E7E7E7">
    <td colspan="4">
        <h4 style="margin: 0 0 0 10px">Parent Tables</h4>
    </td>
</tr>
<tr>
    <th style="width: 400">Table</th>
    <th style="width: 50">Count</th>
    <th style="width: 50"></th>
    <th style="width: 50"></th>
</tr>
HTML;
            foreach ($brokenTables['parent'] as $parentTable => $brokenItemsCount) {

                $html .= <<<HTML
<tr>
    <td>
        <a href="{$infoUrl}?table[]={$parentTable}"
           target="_blank" title="Show Ids" style="text-decoration: none;">{$parentTable}</a>
    </td>
    <td>
        {$brokenItemsCount}
    </td>
    <td>
        <input type='button' value="Repair" onclick ="location.href='{$currentUrl}?table[]={$parentTable}'" />
    </td>
    <td>
        <input type="checkbox" name="table[]" value="{$parentTable}" />
    </td>
HTML;
            }
        }

        if (count($brokenTables['children'])) {

            $html .= <<<HTML
<tr height="100%">
    <td><div style="height: 10px;"></div></td>
</tr>
<tr bgcolor="#E7E7E7">
    <td colspan="4">
        <h4 style="margin: 0 0 0 10px">Children Tables</h4>
    </td>
</tr>
<tr>
    <th style="width: 400">Table</th>
    <th style="width: 50">Count</th>
    <th style="width: 50"></th>
    <th style="width: 50"></th>
</tr>
HTML;
            foreach ($brokenTables['children'] as $childrenTable => $brokenItemsCount) {

                $html .= <<<HTML
<tr>
    <td>
        <a href="{$infoUrl}?table[]={$childrenTable}"
           target="_blank" title="Show Ids" style="text-decoration: none;">{$childrenTable}</a>
    </td>
    <td>
        {$brokenItemsCount}
    </td>
    <td>
        <input type='button' value="Repair" onclick ="location.href='{$currentUrl}?table[]={$childrenTable}'" />
    </td>
    <td>
        <input type="checkbox" name="table[]" value="{$childrenTable}" />
    </td>
HTML;
            }
        }

        $html .= <<<HTML
                <tr>
                    <td colspan="4"><hr/></td>
                </tr>
                <tr>
                    <td colspan="4" align="right">
                        <input type="submit" value="Repair Checked">
                    <td>
                </tr>
            </table>
        </form>
    </body>
</html>
HTML;

        return $html;
    }

    /**
     * @title "Show Broken Table IDs"
     * @hidden
     */
    public function showBrokenTableIdsAction()
    {
        $tableNames = $this->getRequest()->getParam('table', array());

        if (empty($tableNames)) {
            return $this->_redirect($this->getUrl('*/*/*', ['action' => 'checkTables']));
        }

        $tableName = array_pop($tableNames);
        $info = $this->getHelper('Module\Database\Repair')->getBrokenRecordsInfo($tableName);

        return '<pre>' .
               "<span>Broken Records '{$tableName}'<span><br>" .
               print_r($info, true);
    }

    // ---------------------------------------

    /**
     * @title "Repair Removed Stores"
     * @description "Command for show and repair removed magento stores"
     */
    public function showRemovedMagentoStoresAction()
    {
        $existsStoreIds = array_keys($this->storeManager->getStores(true));
        $storeRelatedColumns = $this->getHelper('Module\Database\Structure')->getStoreRelatedColumns();

        $usedStoresIds = array();

        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {

                $tempResult = $this->resourceConnection->getConnection()->select()
                    ->distinct()
                    ->from(
                        $this->getHelper('Module\Database\Structure')->getTableNameWithPrefix($tableName),
                        array($columnInfo['name'])
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
                    !empty($matches[2]) && $usedStoresIds = array_merge($usedStoresIds,$matches[2]);
                }
            }
        }

        $usedStoresIds = array_values(array_unique(array_map('intval',$usedStoresIds)));
        $removedStoreIds = array_diff($usedStoresIds, $existsStoreIds);

        if (count($removedStoreIds) <= 0) {
            return $this->getEmptyResultsHtml('No Removed Magento Stores');
        }

        $html = $this->getStyleHtml();

        $removedStoreIds = implode(', ', $removedStoreIds);
        $repairStoresAction = $this->getUrl('*/*/*', ['action' => 'repairRemovedMagentoStore']);

        $html .= <<<HTML
<h2 style="margin: 20px 0 0 10px">Removed Magento Stores
    <span style="color: #808080; font-size: 15px;">(%count% entries)</span>
</h2>

<span style="display:inline-block; margin: 20px 20px 20px 10px;">
    Removed Store IDs: {$removedStoreIds}
</span>

<form action="{$repairStoresAction}" method="get">
    <input name="replace_from" value="" type="text" placeholder="replace from id" required/>
    <input name="replace_to" value="" type="text" placeholder="replace to id" required />
    <button type="submit">Repair</button>
</form>
HTML;

        return str_replace('%count%', count($removedStoreIds), $html);
    }

    /**
     * @title "Repair Removed Store"
     * @hidden
     */
    public function repairRemovedMagentoStoreAction()
    {
        $replaceIdFrom = $this->getRequest()->getParam('replace_from');
        $replaceIdTo   = $this->getRequest()->getParam('replace_to');

        if (is_null($replaceIdFrom) || is_null($replaceIdTo)) {
            $this->getMessageManager()->addError('Required params are not presented.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageToolsTabUrl());
        }

        $replaceIdFrom = (int)$replaceIdFrom;
        $replaceIdTo   = (int)$replaceIdTo;

        $storeRelatedColumns = $this->getHelper('Module\Database\Structure')->getStoreRelatedColumns();
        foreach ($storeRelatedColumns as $tableName => $columnsInfo) {
            foreach ($columnsInfo as $columnInfo) {

                if ($columnInfo['type'] == 'int') {

                    $this->resourceConnection->getConnection()->update(
                        $this->getHelper('Module\Database\Structure')->getTableNameWithPrefix($tableName),
                        array($columnInfo['name'] => $replaceIdTo),
                        "`{$columnInfo['name']}` = {$replaceIdFrom}"
                    );

                    continue;
                }

                // json ("store_id":"10" | "store_id":10, | "store_id":10})
                $bind = array($columnInfo['name'] => new \Zend_Db_Expr(
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
                ));

                $this->resourceConnection->getConnection()->update(
                    $this->getHelper('Module\Database\Structure')->getTableNameWithPrefix($tableName),
                    $bind,
                    "`{$columnInfo['name']}` LIKE '%store_id\":\"{$replaceIdFrom}\"%' OR
                     `{$columnInfo['name']}` LIKE '%store_id\":{$replaceIdFrom},%' OR
                     `{$columnInfo['name']}` LIKE '%store_id\":{$replaceIdFrom}}%'"
                );
            }
        }

        return $this->_redirect('*/*/*', ['action' => 'showRemovedMagentoStores']);
    }

    // ---------------------------------------

    /**
     * @title "Repair Listing Product Structure"
     * @description "Listing -> Listing Product -> Option -> Variation"
     */
    public function repairListingProductStructureAction()
    {
        ini_set('display_errors', 1);

        // -- Listing to un-existed Account
        $accountTable = $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->getSelect()->joinLeft(
            ['ma' => $accountTable],
            'main_table.account_id=ma.id',
            []
        );
        $collection->addFieldToFilter('ma.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array(
            'main_table.id', 'main_table.component_mode'
        ));

        $itemsIdsToDelete = $collection->getColumnValues('id');
        $deletedListings = count($itemsIdsToDelete);

        if ($itemsIdsToDelete) {

            /* @var $item \Ess\M2ePro\Model\Listing\Product */
            $item = $collection->getFirstItem();

            $item->getResource()->getConnection()->delete(
                $item->getResource()->getMainTable(),
                array('id IN (?)' => $itemsIdsToDelete)
            );

            $item->getResource()->getConnection()->delete(
                $item->getChildObject()->getResource()->getMainTable(),
                array('listing_id IN (?)' => $itemsIdsToDelete)
            );
        }
        // --

        // -- Listing_Product to un-existed Listing
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            ['ml' => $listingTable],
            'main_table.listing_id=ml.id',
            []
        );
        $collection->addFieldToFilter('ml.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array(
            'main_table.id', 'main_table.component_mode'
        ));

        $itemsIdsToDelete = $collection->getColumnValues('id');
        $deletedProducts = count($itemsIdsToDelete);

        if ($itemsIdsToDelete) {

            /* @var $item \Ess\M2ePro\Model\Listing\Product */
            $item = $collection->getFirstItem();

            $item->getResource()->getConnection()->delete(
                $item->getResource()->getMainTable(),
                array('id IN (?)' => $itemsIdsToDelete)
            );

            $item->getResource()->getConnection()->delete(
                $item->getChildObject()->getResource()->getMainTable(),
                array('listing_product_id IN (?)' => $itemsIdsToDelete)
            );
        }
        // --

        // -- Listing_Product_Variation to un-existed Listing_Product OR with no Listing_Product_Variation_Option
        $listingProductTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getMainTable();

        $listingProductVariationOptionTable = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
            ->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing\Product\Variation')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mlp' => $listingProductTable],
            'main_table.listing_product_id=mlp.id',
            []
        );
        $collection->getSelect()->joinLeft(
            ['mlpvo' => $listingProductVariationOptionTable],
            'main_table.id=mlpvo.listing_product_variation_id',
            []
        );

        $collection->getSelect()->where('mlp.id IS NULL OR mlpvo.id IS NULL');
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array(
            'main_table.id', 'main_table.component_mode'
        ));

        $itemsIdsToDelete = $collection->getColumnValues('id');
        $deletedVariations = count($itemsIdsToDelete);

        if ($itemsIdsToDelete) {

            /* @var $item \Ess\M2ePro\Model\Listing\Product\Variation */
            $item = $collection->getFirstItem();

            $item->getResource()->getConnection()->delete(
                $item->getResource()->getMainTable(),
                array('id IN (?)' => $itemsIdsToDelete)
            );

            $item->getResource()->getConnection()->delete(
                $item->getChildObject()->getResource()->getMainTable(),
                array('listing_product_variation_id IN (?)' => $itemsIdsToDelete)
            );
        }
        // --

        // -- Listing_Product_Variation_Option to un-existed Listing_Product_Variation
        $listingProductVariationTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')
            ->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mlpv' => $listingProductVariationTable],
            'main_table.listing_product_variation_id=mlpv.id',
            []
        );
        $collection->addFieldToFilter('mlpv.id', array('null' => true));
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array(
            'main_table.id', 'main_table.component_mode'
        ));

        $itemsIdsToDelete = $collection->getColumnValues('id');
        $deletedOptions = count($itemsIdsToDelete);

        if ($itemsIdsToDelete) {

            /* @var $item \Ess\M2ePro\Model\Listing\Product\Variation\Option */
            $item = $collection->getFirstItem();

            $item->getResource()->getConnection()->delete(
                $item->getResource()->getMainTable(),
                array('id IN (?)' => $itemsIdsToDelete)
            );

            $item->getResource()->getConnection()->delete(
                $item->getChildObject()->getResource()->getMainTable(),
                array('listing_product_variation_option_id IN (?)' => $itemsIdsToDelete)
            );
        }
        // --

        $result  = '';
        $result .= sprintf('Deleted options: %d <br/>', $deletedOptions);
        $result .= sprintf('Deleted variations: %d <br/>', $deletedVariations);
        $result .= sprintf('Deleted products: %d <br/>', $deletedProducts);
        $result .= sprintf('Deleted listings: %d <br/>', $deletedListings);

        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        $result .= <<<HTML
<span style="margin: 20px 0 0 0">
    <a href="{$backUrl}">[back]</a>
</span>
HTML;
        return $result;
    }

    /**
     * @title "Repair OrderItem => Order Structure"
     * @description "OrderItem->getOrder() => remove OrderItem if is need"
     */
    public function repairOrderItemOrderStructureAction()
    {
        ini_set('display_errors', 1);

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Order\Item')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mo' => $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable()],
            'main_table.order_id=mo.id',
            []
        );
        $collection->addFieldToFilter('mo.id', array('null' => true));

        $deletedOrderItems = 0;

        /* @var $item \Ess\M2ePro\Model\Order\Item */
        while ($item = $collection->fetchItem()) {
            $item->delete() && $deletedOrderItems++;
        }

        $result = sprintf('Deleted OrderItems records: %d', $deletedOrderItems);
        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        $result .= <<<HTML
<br><span style="margin: 20px 0 0 0">
    <a href="{$backUrl}">[back]</a>
</span>
HTML;
        return $result;
    }

    /**
     * @title "Repair eBay ItemID N\A"
     * @description "Repair Item is Listed but have N\A Ebay Item ID"
     */
    public function repairEbayItemIdStructureAction()
    {
        ini_set('display_errors', 1);
        $items = 0;

        $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            array('ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()),
            '`second_table`.`ebay_item_id` = `ei`.`id`',
            array('item_id' => 'item_id')
        );
        $collection->addFieldToFilter('status',
                                      array('nin' => array(\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                                                           \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN)));

        $collection->addFieldToFilter('item_id', array('null' => true));

        while ($item = $collection->fetchItem()) {

            $item->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)->save();
            $items++;
        }

        $result = sprintf('Processed items: %d', $items);
        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        $result .= <<<HTML
<br><span style="margin: 20px 0 0 0">
    <a href="{$backUrl}">[back]</a>
</span>
HTML;
        return $result;
    }

    /**
     * @title "Repair Amazon Products without variations"
     * @description "Repair Amazon Products without variations"
     * @new_line
     */
    public function repairAmazonProductWithoutVariationsAction()
    {
        ini_set('display_errors', 1);
        $items = 0;

        $listingProductVariationTable = $this->activeRecordFactory->getObject('Listing\Product\Variation')
            ->getResource()
            ->getMainTable();

        $collection = $this->parentFactory->getObject(Amazon::NICK, 'Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            array('mlpv' => $listingProductVariationTable),
            '`second_table`.`listing_product_id` = `mlpv`.`listing_product_id`',
            array()
        );
        $collection->addFieldToFilter('is_variation_product', 1);
        $collection->addFieldToFilter('is_variation_product_matched', 1);
        $collection->addFieldToFilter('mlpv.id', array('null' => true));

        while ($item = $collection->fetchItem()) {

            $item->getChildObject()->setData('is_variation_product_matched', 0)->save();
            $items++;
        }

        $result = sprintf('Processed items: %d', $items);
        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        $result .= <<<HTML
<br><span style="margin: 20px 0 0 0">
    <a href="{$backUrl}">[back]</a>
</span>
HTML;
        return $result;
    }

    //########################################

    /**
     * @title "Check Server Connection"
     * @description "Send test request to server and check connection"
     */
    public function serverCheckConnectionAction()
    {
        try {

            $response = $this->getHelper('Server\Request')->single(
                array('timeout' => 30), null, null, false, false
            );

        } catch (Connection $e) {

            $result = "<h2>{$e->getMessage()}</h2><pre><br/>";
            $additionalData = $e->getAdditionalData();

            if (!empty($additionalData['curl_info'])) {
                $result .= '</pre><h2>Report</h2><pre>';
                $result .= json_encode($additionalData['curl_info'], JSON_PRETTY_PRINT);
                $result .= '</pre>';
            }

            if (!empty($additionalData['curl_error_number']) && !empty($additionalData['curl_error_message'])) {
                $result .= '<h2 style="color:red;">Errors</h2>';
                $result .= $additionalData['curl_error_number'] .': '
                    . $additionalData['curl_error_message'] . '<br/><br/>';
            }

            return $result;

        } catch (\Exception $e) {
            return "<h2>{$e->getMessage()}</h2><pre><br/>";
        }

        $result = '<h2>Response</h2><pre>';
        $result .= json_encode($this->getHelper('Data')->jsonDecode($response['body']), JSON_PRETTY_PRINT);
        $result .= '</pre>';

        $result .= '</pre><h2>Report</h2><pre>';
        $result .= json_encode($response['curl_info'], JSON_PRETTY_PRINT);
        $result .= '</pre>';

        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        $result .= <<<HTML
<br><span style="margin: 20px 0 0 0">
    <a href="{$backUrl}">[back]</a>
</span>
HTML;
        return $result;
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->getHelper('View\ControlPanel')->getPageToolsTabUrl();

        return <<<HTML
<h2 style="margin: 20px 0 0 10px">
    {$messageText} <span style="color: grey; font-size: 10px;">
    <a href="{$backUrl}">[back]</a>
</h2>
HTML;
    }

    //########################################
}