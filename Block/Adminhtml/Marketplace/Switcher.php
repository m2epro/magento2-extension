<?php

namespace Ess\M2ePro\Block\Adminhtml\Marketplace;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Component\Switcher
{
    protected $paramName = 'marketplace';

    //########################################

    public function getLabel()
    {
        return $this->__('Marketplace');
    }

    protected function loadItems()
    {
        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('component_mode', 'ASC')
            ->setOrder('sorder', 'ASC');

        if (!is_null($this->getData('component_mode'))) {
            $collection->addFieldToFilter('component_mode', $this->getData('component_mode'));
        }

        if (!$collection->count()) {
            $this->items = array();
            return;
        }

        if ($collection->count() < 2) {
            $this->hasDefaultOption = false;
            $this->setIsDisabled(true);
        }

        $items = array();

        foreach ($collection as $marketplace) {
            /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
            $items[$marketplace->getComponentMode()]['value'][] = array(
                'value' => $marketplace->getId(),
                'label' => $marketplace->getTitle()
            );
        }

        $this->items = $items;
    }

    //########################################
}