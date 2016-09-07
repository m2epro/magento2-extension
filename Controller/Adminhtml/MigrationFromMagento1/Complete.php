<?php

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

use Ess\M2ePro\Setup\MigrationFromMagento1;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Complete extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    const SUPPORTED_SOURCE_VERSION = '6.5.0.5';

    protected $dbModifier;

    //########################################

    public function __construct(
        Context $context,
        MigrationFromMagento1 $dbModifier
    ) {
        $this->dbModifier    = $dbModifier;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $this->dbModifier->prepareTablesPrefixes();

        try {
            if (!$this->checkPreconditions()) {
                return $this->getRawResult();
            }

            $this->dbModifier->process();
        } catch (\Exception $exception) {
            $this->getRawResult()->setContents(
                $this->__('Migration is failed. Reason: %error_message%', $exception->getMessage())
            );

            return $this->getRawResult();
        }

        $this->getHelper('Module\Maintenance\General')->disable();

        $this->getRawResult()->setContents(
            $this->__('Migration successfully completed.')
        );

        return $this->getRawResult();
    }

    //########################################

    private function checkPreconditions()
    {
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->resourceConnection->getTableName('m2epro_primary_config'))
            ->where('`group` LIKE ?', '/migrationToMagento2/source/%');

        $sourceParams = [];

        foreach ($this->resourceConnection->getConnection()->fetchAll($select) as $paramRow) {
            $sourceParams[$paramRow['group']][$paramRow['key']] = $paramRow['value'];
        }

        if (!$this->getHelper('Module\Maintenance\General')->isEnabled() ||
            empty($sourceParams['/migrationtomagento2/source/']['is_prepared_for_migration']) ||
            empty($sourceParams['/migrationtomagento2/source/m2epro/']['version'])
        ) {
            $this->getRawResult()->setContents(
                $this->__('Migration is failed. Please read instructions and try again.')
            );
            return false;
        }

        if (empty($sourceParams['/migrationtomagento2/source/m2epro/']['version']) ||
            $sourceParams['/migrationtomagento2/source/m2epro/']['version'] != self::SUPPORTED_SOURCE_VERSION
        ) {
            $this->getRawResult()->setContents(
                $this->__('Source M2ePro version is not supported.')
            );
            return false;
        }

        return true;
    }

    //########################################
}