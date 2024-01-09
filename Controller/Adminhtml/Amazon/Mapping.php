<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

abstract class Mapping extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_mapping');
    }
}
