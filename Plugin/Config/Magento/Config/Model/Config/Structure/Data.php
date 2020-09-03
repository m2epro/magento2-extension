<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure;

use Ess\M2ePro\Helper\View\Configuration;
use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure\Data
 */
class Data extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    private $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function canExecute()
    {
        return true;
    }

    public function aroundGet($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('get', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processGet($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);

        if ($this->helperFactory->getObject('Module\Maintenance')->isEnabled() ||
            !$this->helperFactory->getObject('Module')->areImportantTablesExist()
        ) {
            unset($result['sections'][Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::WALMART_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::ADVANCED_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        } elseif ($this->helperFactory->getObject('Module')->isDisabled()) {
            unset($result['sections'][Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][Configuration::WALMART_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        }

        /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
        $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
        if (!$wizard->isStarted()) {
            unset($result['sections'][Configuration::ADVANCED_SECTION_WIZARD]);
        }

        return $result;
    }

    //########################################
}
