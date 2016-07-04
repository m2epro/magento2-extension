<?php

namespace Ess\M2ePro\Block\Adminhtml\Account;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Component\Switcher
{
    protected $paramName = 'account';

    //########################################

    public function getLabel()
    {
        if ($this->getData('component_mode') == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return $this->__('Account');
        }

        return $this->__($this->getComponentLabel('%component% Account'));
    }

    public function getItems()
    {
        $collection = $this->activeRecordFactory->getObject('Account')->getCollection()
            ->setOrder('component_mode', 'ASC')
            ->setOrder('title', 'ASC');

        if (!is_null($this->getData('component_mode'))) {
            $collection->addFieldToFilter('component_mode', $this->getData('component_mode'));
        }

        if ($collection->getSize() < 2) {
            return array();
        }

        $items = array();

        foreach ($collection as $account) {
            /** @var $account \Ess\M2ePro\Model\Account */

            if (!isset($items[$account->getComponentMode()]['label'])) {
                $label = '';
                if (isset($componentTitles[$account->getComponentMode()])) {
                    $label = $componentTitles[$account->getComponentMode()];
                }

                $items[$account->getComponentMode()]['label'] = $label;
            }

            $items[$account->getComponentMode()]['value'][] = array(
                'value' => $account->getId(),
                'label' => $account->getTitle()
            );
        }

        return $items;
    }

    //########################################

    public function getDefaultOptionName()
    {
        return $this->__('All Accounts');
    }

    //########################################
}