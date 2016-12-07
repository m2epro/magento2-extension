<?php

namespace Ess\M2ePro\Block\Adminhtml\Account;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Component\Switcher
{
    protected $paramName = 'account';

    //########################################

    public function getLabel()
    {
        return $this->__('Account');
    }

    protected function loadItems()
    {
        $collection = $this->activeRecordFactory->getObject('Account')->getCollection()
            ->setOrder('component_mode', 'ASC')
            ->setOrder('title', 'ASC');

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

        foreach ($collection as $account) {

            $accountTitle = $this->filterManager->truncate(
                $account->getTitle(), ['length' => 15]
            );

            /** @var $account \Ess\M2ePro\Model\Account */
            $items[$account->getComponentMode()]['value'][] = array(
                'value' => $account->getId(),
                'label' => $accountTitle
            );
        }

        $this->items = $items;
    }

    //########################################
}