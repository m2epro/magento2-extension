<?php

namespace Ess\M2ePro\Block\Adminhtml\Marketplace;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Component\Switcher
{
    protected $paramName = 'marketplace';

    //########################################

    public function getLabel()
    {
        if ($this->getData('component_mode') == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return $this->__('Marketplace');
        }

        return $this->__($this->getComponentLabel('%component% Marketplace'));
    }

    public function getItems()
    {
        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('component_mode', 'ASC')
            ->setOrder('sorder', 'ASC');

        if (!is_null($this->getData('component_mode'))) {
            $collection->addFieldToFilter('component_mode', $this->getData('component_mode'));
        }

        if ($collection->getSize() < 2) {
            return array();
        }

        $componentTitles = $this->getHelper('Component')->getComponentsTitles();

        $items = array();

        foreach ($collection as $marketplace) {
            /** @var $marketplace \Ess\M2ePro\Model\Marketplace */

            if (!isset($items[$marketplace->getComponentMode()]['label'])) {
                $label = '';
                if (isset($componentTitles[$marketplace->getComponentMode()])) {
                    $label = $componentTitles[$marketplace->getComponentMode()];
                }
                $items[$marketplace->getComponentMode()]['label'] = $label;
            }

            $items[$marketplace->getComponentMode()]['value'][] = array(
                'value' => $marketplace->getId(),
                'label' => $marketplace->getTitle()
            );
        }

        return $items;
    }

    //########################################

    public function getDefaultOptionName()
    {
        return $this->__('All Marketplaces');
    }

    //########################################
}