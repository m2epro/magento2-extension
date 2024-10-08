<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

abstract class AbstractProductType extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_configuration_product_types');
    }
}
