<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

abstract class ProductType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_product_types');
    }
}
