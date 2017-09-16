<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner\Service;

class ErrorHandler extends \Magento\Framework\App\ErrorHandler
{
    /** @var \Ess\M2ePro\Helper\Module\Cron\Service */
    private $cronHelper;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Module\Cron\Service $cronHelper
    ){
        $this->cronHelper = $cronHelper;
    }

    //########################################

    public function handler($errorNo, $errorStr, $errorFile, $errorLine)
    {
        if ($this->isHeadersModifiedErrorAppeared($errorStr)) {
            $this->cronHelper->forbidClosingConnection();
        }

        parent::handler($errorNo, $errorStr, $errorFile, $errorLine);
    }

    //########################################

    private function isHeadersModifiedErrorAppeared($errorStr)
    {
        return strpos($errorStr, 'Cannot modify header information - ') !== false;
    }

    //########################################
}
