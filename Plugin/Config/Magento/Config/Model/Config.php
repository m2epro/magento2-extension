<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Config\Magento\Config\Model;

/**
 * Class \Ess\M2ePro\Plugin\Config\Magento\Config\Model\Config
 */
class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    protected function canExecute()
    {
        if ($this->helperFactory->getObject('Module\Maintenance')->isEnabled()) {
            return false;
        }

        return true;
    }

    public function aroundSave(\Magento\Config\Model\Config $interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('save', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processSave(\Magento\Config\Model\Config $interceptor, \Closure $callback, array $arguments)
    {
        $saveData = $interceptor->getData();

        $availableSections = [
            \Ess\M2ePro\Helper\View\Configuration::EBAY_SECTION_COMPONENT,
            \Ess\M2ePro\Helper\View\Configuration::AMAZON_SECTION_COMPONENT,
            \Ess\M2ePro\Helper\View\Configuration::WALMART_SECTION_COMPONENT,
            \Ess\M2ePro\Helper\View\Configuration::ADVANCED_SECTION_COMPONENT
        ];

        if (!isset($saveData['section']) ||
            !in_array($saveData['section'], $availableSections)
        ) {
            return $callback(...$arguments);
        }

        $groups = $saveData['groups'];

        if (isset($groups['ebay_mode']['fields']['ebay_mode_field']['value'])) {
            $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
                '/component/ebay/',
                'mode',
                (int)$groups['ebay_mode']['fields']['ebay_mode_field']['value']
            );
        }

        if (isset($groups['amazon_mode']['fields']['amazon_mode_field']['value'])) {
            $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
                '/component/amazon/',
                'mode',
                (int)$groups['amazon_mode']['fields']['amazon_mode_field']['value']
            );
        }

        if (isset($groups['walmart_mode']['fields']['walmart_mode_field']['value'])) {
            $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
                '/component/walmart/',
                'mode',
                (int)$groups['walmart_mode']['fields']['walmart_mode_field']['value']
            );
        }

        if (isset($groups['module_mode']['fields']['module_mode_field']['value'])) {
            $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
                null,
                'is_disabled',
                (int)$groups['module_mode']['fields']['module_mode_field']['value']
            );
        }

        return $interceptor;
    }

    //########################################
}
