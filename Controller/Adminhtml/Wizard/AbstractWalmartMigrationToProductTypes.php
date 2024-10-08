<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Wizard;

abstract class AbstractWalmartMigrationToProductTypes extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Wizard
{
    protected function getNick()
    {
        return \Ess\M2ePro\Helper\View\Walmart::WIZARD_WALMART_MIGRATION_TO_PRODUCT_TYPES_NICK;
    }
}
