<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

abstract class ProductType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_product_types');
    }
}
