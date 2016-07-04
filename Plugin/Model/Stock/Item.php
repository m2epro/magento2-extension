<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Model\Stock;

class Item
{
    private $eventManager;

    //########################################

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager
    )
    {
        $this->eventManager = $eventManager;
    }

    //########################################

    public function afterBeforeSave($subject, $result)
    {
        $this->eventManager->dispatch(
            'cataloginventory_stock_item_save_before',
            [
                'data_object' => $subject,
                'object' => $subject,
            ]
        );
    }

    public function afterAfterSave($subject, $result)
    {
        $this->eventManager->dispatch(
            'cataloginventory_stock_item_save_after',
            [
                'data_object' => $subject,
                'object' => $subject,
            ]
        );
    }

    //########################################
}