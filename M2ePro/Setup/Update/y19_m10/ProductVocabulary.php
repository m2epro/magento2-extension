<?php

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

// @codingStandardsIgnoreFile

class ProductVocabulary extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $registryTable = $this->getFullTableName('registry');
        $this->installer->run("
UPDATE `{$registryTable}` SET `key` = '/product/variation/vocabulary/server/' WHERE `key` = 'amazon_vocabulary_server';
        ");

        $this->installer->run("
UPDATE `{$registryTable}` SET `key` = '/product/variation/vocabulary/local/' WHERE `key` = 'amazon_vocabulary_local';"
        );

        $this->installer->run("
DELETE FROM `{$registryTable}` WHERE `key` IN ('walmart_vocabulary_server', 'walmart_vocabulary_local');
        ");

        $moduleConfig = $this->getConfigModifier('module');

        $moduleConfig->updateGroup(
            '/product/variation/vocabulary/attribute/auto_action/',
            ['`group` = ?' => '/amazon/vocabulary/attribute/auto_action/']
        );
        $moduleConfig->updateGroup(
            '/product/variation/vocabulary/option/auto_action/',
            ['`group` = ?' => '/amazon/vocabulary/option/auto_action/']
        );

        $moduleConfig->delete('/walmart/vocabulary/option/auto_action/');
        $moduleConfig->delete('/walmart/vocabulary/attribute/auto_action/');
    }

    //########################################
}
