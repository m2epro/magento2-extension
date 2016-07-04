<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

class Prepare extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    protected $magentoConfig = NULL;

    //########################################

    public function __construct(
        \Magento\Config\Model\Config $magentoConfig,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->magentoConfig = $magentoConfig;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $this->getHelper('Module\Maintenance\Setup')->enable();

        try {
            $this->prepareDatabase();
        } catch (\Exception $exception) {
            $this->getRawResult()->setContents(
                $this->__(
                    'Module was not prepared for migration. Reason: %error_message%.',
                    array('error_message' => $exception->getMessage())
                )
            );

            return $this->getRawResult();
        }

        $this->helperFactory->getObject('Magento')->clearCache();

        $this->getRawResult()->setContents(
            $this->__('Module was successfully prepared for migration.')
        );

        return $this->getRawResult();
    }

    //########################################

    private function prepareDatabase()
    {
        $allTables = $this->helperFactory->getObject('Module\Database\Structure')->getMySqlTables();

        foreach ($allTables as $tableName) {
            $this->resourceConnection->getConnection()->dropTable(
                $this->resourceConnection->getTableName($tableName)
            );
        }
    }

    //########################################
}