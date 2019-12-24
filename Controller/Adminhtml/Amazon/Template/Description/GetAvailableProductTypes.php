<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetAvailableProductTypes
 */
class GetAvailableProductTypes extends Description
{
    //########################################

    public function execute()
    {
        $marketplaceId = (int)$this->getRequest()->getPost('marketplace_id');
        $browsenodeId  = $this->getRequest()->getPost('browsenode_id');

        $tableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_category_product_data');

        $queryStmt = $this->resourceConnection->getConnection()
            ->select()
            ->from($tableName)
            ->where('marketplace_id = ?', $marketplaceId)
            ->where('browsenode_id = ?', $browsenodeId)
            ->query();

        $cachedProductTypes = [];

        while ($row = $queryStmt->fetch()) {
            $cachedProductTypes[$row['product_data_nick']] = [
                'product_data_nick'   => $row['product_data_nick'],
                'is_applicable'       => $row['is_applicable'],
                'required_attributes' => $row['required_attributes']
            ];
        }

        $model = $this->modelFactory->getObject('Amazon_Marketplace_Details');
        $model->setMarketplaceId($marketplaceId);

        $allAvailableProductTypes = $model->getProductData();
        $shouldBeUpdatedProductTypes = array_diff(
            array_keys($allAvailableProductTypes),
            array_keys($cachedProductTypes)
        );

        if (!empty($shouldBeUpdatedProductTypes)) {
            $result = $this->updateProductDataNicksInfo($marketplaceId, $browsenodeId, $shouldBeUpdatedProductTypes);
            $cachedProductTypes = array_merge($cachedProductTypes, $result);
        }

        foreach ($cachedProductTypes as $nick => &$productTypeInfo) {
            if (!$productTypeInfo['is_applicable']) {
                unset($cachedProductTypes[$nick]);
                continue;
            }

            $productTypeInfo['title'] = isset($allAvailableProductTypes[$nick])
                ? $allAvailableProductTypes[$nick]['title'] : $nick;

            $productTypeInfo['group'] = isset($allAvailableProductTypes[$nick])
                ? $allAvailableProductTypes[$nick]['group'] : 'Other';

            $productTypeInfo['required_attributes'] = (array)$this->getHelper('Data')->jsonDecode(
                $productTypeInfo['required_attributes']
            );
        }

        $this->setJsonContent([
            'product_data' => $cachedProductTypes,
            'grouped_data' => $this->getGroupedProductDataNicksInfo($cachedProductTypes),
            'recent_data'  => $this->getRecentProductDataNicksInfo($marketplaceId, $cachedProductTypes)
        ]);
        return $this->getResult();
    }

    // ---------------------------------------

    private function updateProductDataNicksInfo($marketplaceId, $browsenodeId, $productDataNicks)
    {
        $marketplaceNativeId = $this->amazonFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getNativeId();

        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'category',
            'get',
            'productsDataInfo',
            [
                'marketplace'        => $marketplaceNativeId,
                'browsenode_id'      => $browsenodeId,
                'product_data_nicks' => $productDataNicks
            ]
        );
        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if ($response === false || empty($response['info'])) {
            return [];
        }

        $insertsData = [];
        foreach ($response['info'] as $dataNickKey => $info) {
            $insertsData[$dataNickKey] = [
                'marketplace_id'      => $marketplaceId,
                'browsenode_id'       => $browsenodeId,
                'product_data_nick'   => $dataNickKey,
                'is_applicable'       => (int)$info['applicable'],
                'required_attributes' => $this->getHelper('Data')->jsonEncode($info['required_attributes'])
            ];
        }

        $tableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_category_product_data');
        $this->resourceConnection->getConnection()->insertMultiple($tableName, $insertsData);

        return $insertsData;
    }

    private function getGroupedProductDataNicksInfo(array $cachedProductTypes)
    {
        $groupedData = [];

        foreach ($cachedProductTypes as $nick => $productTypeInfo) {
            $groupedData[$productTypeInfo['group']][$productTypeInfo['title']] = $productTypeInfo;
        }

        ksort($groupedData);
        foreach ($groupedData as $group => &$productTypes) {
            ksort($productTypes);
        }

        return $groupedData;
    }

    private function getRecentProductDataNicksInfo($marketplaceId, array $cachedProductTypes)
    {
        $recentProductDataNicks = [];

        foreach ($this->getHelper('Component_Amazon_ProductData')->getRecent($marketplaceId) as $nick) {
            if (!isset($cachedProductTypes[$nick]) || !$cachedProductTypes[$nick]['is_applicable']) {
                continue;
            }

            $recentProductDataNicks[$nick] = [
                'title'               => $cachedProductTypes[$nick]['title'],
                'group'               => $cachedProductTypes[$nick]['group'],
                'product_data_nick'   => $nick,
                'is_applicable'       => 1,
                'required_attributes' => $cachedProductTypes[$nick]['required_attributes']
            ];
        }

        return $recentProductDataNicks;
    }

    //########################################
}
