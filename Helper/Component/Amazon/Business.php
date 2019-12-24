<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

/**
 * Class \Ess\M2ePro\Helper\Component\Amazon\Business
 */
class Business extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/amazon/business/', 'mode');
    }

    public function isVatCalculationServiceEnabled()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return (bool)$this->moduleConfig->getGroupValue(
            '/amazon/business/vat_calculation_service/',
            'mode'
        );
    }

    public function isInvoiceCreationDisabled()
    {
        if (!$this->isVatCalculationServiceEnabled()) {
            return false;
        }

        return (bool)$this->moduleConfig->getGroupValue(
            '/amazon/business/vat_calculation_service/',
            'is_invoice_creation_disabled'
        );
    }

    //########################################
}
