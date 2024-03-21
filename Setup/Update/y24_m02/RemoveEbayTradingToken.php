<?php

namespace Ess\M2ePro\Setup\Update\y24_m02;

class RemoveEbayTradingToken extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->addAndUpdateIsTokenExistField();
        $this->dropColumns();
    }

    private function addAndUpdateIsTokenExistField()
    {
        $modifier = $this->getTableModifier('ebay_account');
        $modifier->addColumn(
            'is_token_exist',
            'SMALLINT NOT NULL',
            0,
            'user_id'
        );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_account'),
            [
                'is_token_exist' => new \Zend_Db_Expr('sell_api_token_session IS NOT NULL')
            ]
        );
    }

    private function dropColumns()
    {
        $modifier = $this->getTableModifier('ebay_account');
        $modifier->dropColumn('token_session')
                 ->dropColumn('token_expired_date')
                 ->dropColumn('sell_api_token_session')
                 ->commit();
    }
}
