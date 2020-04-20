<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Marketplace;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher
 */
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

        if ($this->getData('component_mode') !== null) {
            $collection->addFieldToFilter('component_mode', $this->getData('component_mode'));
        }

        if (!$collection->getSize()) {
            $this->items = [];
            return;
        }

        if ($collection->getSize() < 2) {
            $this->hasDefaultOption = false;
            $this->setIsDisabled(true);
        }

        $items = [];

        foreach ($collection->getItems() as $marketplace) {
            /** @var $marketplace \Ess\M2ePro\Model\Marketplace */
            $items[$marketplace->getComponentMode()]['value'][] = [
                'value' => $marketplace->getId(),
                'label' => $marketplace->getTitle()
            ];
        }

        $this->items = $items;
    }

    //########################################
}
