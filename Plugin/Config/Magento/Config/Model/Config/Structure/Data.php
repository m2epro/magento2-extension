<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config\Structure;

class Data extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    private $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
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

        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() ||
            (
                $this->helperFactory->getObject('Module\Maintenance\Debug')->isEnabled() &&
                !$this->helperFactory->getObject('Module\Maintenance\Debug')->isOwner()
            )
        )
        {
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::BUY_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);

        } elseif ($this->helperFactory->getObject('Module')->isDisabled()) {

            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::EBAY_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::AMAZON_SECTION_COMPONENT]);
            unset($result['sections'][\Ess\M2ePro\Helper\View\Configuration::BUY_SECTION_COMPONENT]);

            unset($result['sections']['payment']['children']['m2epropayment']);
            unset($result['sections']['carriers']['children']['m2eproshipping']);
        }

        return $result;
    }

    //########################################
}