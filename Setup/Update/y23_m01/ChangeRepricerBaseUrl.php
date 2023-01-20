<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

class ChangeRepricerBaseUrl extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            [
                "value" => "https://repricer.m2e.cloud/connector/m2epro/",
            ],
            [
                "`group` = '/amazon/repricing/' AND `key` = 'base_url'"
            ]
        );
    }
}
