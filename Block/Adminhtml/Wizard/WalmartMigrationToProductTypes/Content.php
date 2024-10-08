<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Wizard\WalmartMigrationToProductTypes;

class Content extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartMigrationToProductTypesContent');
        $this->setTemplate('wizard/walmartMigrationToProductTypes/content.phtml');
    }

    public function getSyncUrl(): string
    {
        return $this->getUrl('*/*/sync');
    }
}
