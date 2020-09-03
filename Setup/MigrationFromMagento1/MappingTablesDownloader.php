<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader
 */
class MappingTablesDownloader
{
    const ROWS_PER_ONE_CALL = 50000;

    /** @var string */
    private $baseUrl;

    /** @var bool */
    private $isNeedToDisableM1;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Factory */
    private $helperFactory;

    /** @var Mapper */
    private $mapper;

    /** @var array */
    private $mappingRecordsCount;

    /** @var string */
    private $licenseKey;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        Mapper $mapper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mapper             = $mapper;
        $this->helperFactory      = $helperFactory;
    }

    //########################################

    /**
     * @param string $baseUrl
     * @return $this
     */
    public function setM1BaseUrl($baseUrl)
    {
        $this->baseUrl = trim($baseUrl, '/');
        return $this;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setIsNeedToDisableM1($flag)
    {
        $this->isNeedToDisableM1 = (bool)$flag;
        return $this;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isDownloadComplete()
    {
        /** @var MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        $configTable = $wizard->getM1TablesPrefix() . 'm2epro_config';
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($configTable, 'value')
            ->where('`group` = ?', '/migrationfrommagento1/unexpected/')
            ->where('`key` = ?', 'is_mapping_tables_download_complete');

        return (bool) $this->resourceConnection->getConnection()->fetchOne($select);
    }

    /**
     * @param $domain
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function resolveM1Endpoint($domain)
    {
        $domain = trim($domain, '/');

        foreach (['https://', 'http://'] as $protocol) {
            foreach (['www.', ''] as $web) {
                foreach (['', 'index.php/'] as $index) {
                    $baseUrl = $protocol . $web . $domain . '/' . $index;
                    $url = $baseUrl . 'M2ePro/unexpectedMigrationToM2/checkConnection';
                    $curlResource = curl_init();

                    curl_setopt($curlResource, CURLOPT_URL, $url);
                    curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curlResource, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($curlResource, CURLOPT_TIMEOUT, 30);

                    $response = curl_exec($curlResource);

                    if (curl_getinfo($curlResource)['http_code'] === 200 && $response === 'success') {
                        return $baseUrl;
                    }
                }
            }
        }

        throw new \Ess\M2ePro\Model\Exception\Logic(
            $this->helperFactory->getObject('Module\Translation')->translate([
                'Failed to upload M2E Pro data because the provided URL address is wrong or not accessible.
                Please ensure you enter the correct URL, then click Continue to try again. 
                If the issue persists, follow <a href="%url%" target="_blank">these instructions</a> to complete 
                the migration process.',
                $this->helperFactory->getObject('Module_Support')->getKnowledgebaseArticleUrl('1600682')
            ])
        );
    }

    /**
     * @throws \Zend_Db_Exception
     * @throws \Exception
     */
    public function download()
    {
        $postData = [
            'license_key' => $this->getLicenseKey(),
            'disable_module' => $this->isNeedToDisableM1
        ];
        $prepareUrl = $this->getM1BaseUrl()  . '/M2ePro/unexpectedMigrationToM2/prepare';

        $curlResource = $this->getCurlResource($prepareUrl, $postData);

        $responseData = $this->helperFactory->getObject('Data')->jsonDecode(curl_exec($curlResource));
        $responseCode = curl_getinfo($curlResource)['http_code'];

        if ($responseCode === 401) {
            throw new \Exception('Wrong license key.');
        }

        if ($responseCode !== 200 || !is_array($responseData)) {

            throw new \Exception(
                $this->helperFactory->getObject('Module\Translation')->translate([
                    'Failed to prepare M2E Pro data for the uploading. Reason: %REASON%
                    Please try again by clicking <b>Continue</b>. If the issue persists, contact Support.',
                    isset($responseData['error_message']) ? $responseData['error_message'] : ''
                ])
            );
        }

        $this->mappingRecordsCount = $responseData;

        $this->createProductsMapTable();
        $this->createOrdersMapTable();
        $this->createStoresMapTable();
        $this->createCategoriesMapTable();

        /** @var MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        $configTable = $wizard->getM1TablesPrefix() . 'm2epro_config';

        $this->resourceConnection->getConnection()
            ->insert(
                $configTable,
                [
                    'group' => '/migrationfrommagento1/unexpected/',
                    'key' => 'is_mapping_tables_download_complete',
                    'value' => 1
                ]
            );
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getLicenseKey()
    {
        if (empty($this->licenseKey)) {
            /** @var MigrationFromMagento1 $wizard */
            $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
            $configTable = $wizard->getM1TablesPrefix() . 'm2epro_config';
            $select = $this->resourceConnection->getConnection()
                ->select()
                ->from($configTable, 'value')
                ->where('`group` = ?', '/license/')
                ->where('`key` = ?', 'key');

            $this->licenseKey = $this->resourceConnection->getConnection()->fetchOne($select);
        }

        return $this->licenseKey;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getM1BaseUrl()
    {
        if (empty($this->baseUrl)) {
            throw new \Exception('Magento 1 base url was not set.');
        }

        return $this->baseUrl;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Exception
     */
    private function createProductsMapTable()
    {
        $mapTableName = $this->mapper->getMapTableName('magento_products');

        if ($this->getConnection()->isTableExists($mapTableName)){
            $this->getConnection()->dropTable($mapTableName);
        }

        $productsMapTable = $this->getConnection()->newTable($mapTableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            );

        $this->getConnection()->createTable($productsMapTable);
        $this->copyMappingData('magento_products');
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Exception
     */
    private function createOrdersMapTable()
    {
        $mapTableName = $this->mapper->getMapTableName('magento_orders');

        if ($this->getConnection()->isTableExists($mapTableName)){
            $this->getConnection()->dropTable($mapTableName);
        }

        $ordersMapTable = $this->getConnection()->newTable($mapTableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                'magento_order_num',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            );

        $this->getConnection()->createTable($ordersMapTable);
        $this->copyMappingData('magento_orders');
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Exception
     */
    private function createStoresMapTable()
    {
        $mapTableName = $this->mapper->getMapTableName('magento_stores');

        if ($this->getConnection()->isTableExists($mapTableName)){
            $this->getConnection()->dropTable($mapTableName);
        }

        $storesMapTable = $this->getConnection()->newTable($mapTableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'store_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            );

        $this->getConnection()->createTable($storesMapTable);
        $this->copyMappingData('magento_stores');
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Exception
     */
    private function createCategoriesMapTable()
    {
        $mapTableName = $this->mapper->getMapTableName('magento_categories');

        if ($this->getConnection()->isTableExists($mapTableName)){
            $this->getConnection()->dropTable($mapTableName);
        }

        $categoriesMapTable = $this->getConnection()->newTable($mapTableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                'category_path',
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            );

        $this->getConnection()->createTable($categoriesMapTable);
        $this->copyMappingData('magento_categories');
    }

    /**
     * @param $mappingTable
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function copyMappingData($mappingTable)
    {
        $postData = [
            'license_key'   => $this->getLicenseKey(),
            'mapping_table' => $mappingTable,
            'limit'         => self::ROWS_PER_ONE_CALL
        ];

        $getMappingDataUrl = $this->getM1BaseUrl()  . '/M2ePro/unexpectedMigrationToM2/getMappingTableData';

        for ($offset = 0; $offset < $this->mappingRecordsCount[$mappingTable]; $offset += self::ROWS_PER_ONE_CALL) {

            $postData['offset'] = $offset;

            $curlResource = $this->getCurlResource($getMappingDataUrl, $postData);
            $responseData = $this->helperFactory->getObject('Data')->jsonDecode(curl_exec($curlResource));
            $responseCode = curl_getinfo($curlResource)['http_code'];

            if ($responseCode === 401) {
                throw new \Exception('Wrong license key.');
            }

            if ($responseCode !== 200 || !is_array($responseData)) {

                throw new \Exception(
                    $this->helperFactory->getObject('Module\Translation')->translate([
                        'Failed to upload M2E Pro data. Reason: %REASON% Please try again by clicking <b>Continue</b>. 
                        If the issue persists, contact Support.',
                        isset($responseData['error_message']) ? $responseData['error_message'] : ''
                    ])
                );
            }

            $this->getConnection()->insertMultiple($this->mapper->getMapTableName($mappingTable), $responseData);
        }
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @param string $url
     * @param array $postData
     * @return false|resource
     */
    private function getCurlResource($url, array $postData)
    {
        $curlResource = curl_init();

        curl_setopt($curlResource, CURLOPT_URL, $url);

        curl_setopt($curlResource, CURLOPT_POST, true);
        curl_setopt($curlResource, CURLOPT_POSTFIELDS, http_build_query($postData, '', '&'));

        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlResource, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlResource, CURLOPT_TIMEOUT, 3600);

        return $curlResource;
    }

    //########################################
}
