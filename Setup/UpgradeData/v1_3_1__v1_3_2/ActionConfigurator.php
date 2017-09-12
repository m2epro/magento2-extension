<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ActionConfigurator extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['processing'];
    }

    public function execute()
    {
        $processingTable = $this->getFullTableName('processing');
        if ($this->getConnection()->isTableExists($processingTable)) {

            $processingsStmt = $this->getConnection()->query("
SELECT * FROM {$processingTable}
WHERE `model` LIKE 'Amazon\Connector\Product\%' OR
      `model` LIKE 'Ebay\Connector\Item\%';
");

            while ($processing = $processingsStmt->fetch(\PDO::FETCH_ASSOC)) {

                if (empty($processing['params'])) {
                    continue;
                }

                $params = (array)@json_decode($processing['params'], true);
                if (!isset($params['responser_params']['products']) && !isset($params['responser_params']['product'])) {
                    continue;
                }

                if (isset($params['responser_params']['products'])) {
                    $productsData = (array)$params['responser_params']['products'];
                } else {
                    $productsData = array($params['responser_params']['product']);
                }

                $isDataChanged = false;

                foreach ($productsData as &$productData) {
                    if (!isset($productData['configurator']['mode'])) {
                        continue;
                    }

                    $isDataChanged = true;

                    if ($productData['configurator']['mode'] == 'full') {
                        $productData['configurator']['is_default_mode'] = true;
                    } else {
                        $productData['configurator']['is_default_mode'] = false;
                    }

                    unset($productData['configurator']['mode']);

                    if (strpos($processing['model'], 'Amazon') === false ||
                        !isset($productData['configurator']['allowed_data_types'])
                    ) {
                        continue;
                    }

                    $allowedDataTypes = $productData['configurator']['allowed_data_types'];

                    $priceDataTypeIndex = array_search('price', $allowedDataTypes);

                    if ($priceDataTypeIndex === false) {
                        continue;
                    }

                    unset($allowedDataTypes[$priceDataTypeIndex]);
                    $allowedDataTypes[] = 'regular_price';

                    $productData['configurator']['allowed_data_types'] = $allowedDataTypes;
                }

                if (!$isDataChanged) {
                    continue;
                }

                if (isset($params['responser_params']['products'])) {
                    $params['responser_params']['products'] = $productsData;
                } else {
                    $params['responser_params']['product'] = reset($productsData);
                }

                $this->getConnection()->update(
                    $processingTable,
                    array('params' => json_encode($params)),
                    array('id = ?' => $processing['id'])
                );
            }
        }
    }

    //########################################
}