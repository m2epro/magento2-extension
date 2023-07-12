<?php

namespace Ess\M2ePro\Setup\Update\y23_m06;

class AddEbayBlockingErrorSetting extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addColumnToListingProductTable();
        $this->addRetryHoursConfig();
        $this->addEbayBlockingErrorsListConfig();
    }

    private function addColumnToListingProductTable(): void
    {
        $this->getTableModifier('listing_product')
             ->addColumn(
                 'last_blocking_error_date',
                 'DATETIME',
                 'NULL',
                 'component_mode',
                 false
             )
             ->commit();
    }

    private function addRetryHoursConfig(): void
    {
        $this->getConfigModifier('module')->insert(
            '/blocking_errors/ebay/',
            'retry_seconds',
            28800
        );
    }

    private function addEbayBlockingErrorsListConfig(): void
    {
        $tagList = [
            '17',
            '36',
            '70',
            '231',
            '106',
            '240',
            '21916750',
            '21916799',
            '21919136',
            '21919188',
            '21919301',
            '21919303',
        ];

        $this->getConfigModifier('module')->insert(
            '/blocking_errors/ebay/',
            'errors_list',
            json_encode($tagList)
        );
    }
}
