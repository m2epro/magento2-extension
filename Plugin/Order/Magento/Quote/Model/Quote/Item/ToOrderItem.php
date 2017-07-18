<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento\Quote\Model\Quote\Item;

class ToOrderItem extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    protected $eventManager;

    //########################################

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->eventManager = $eventManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function aroundConvert($interceptor, \Closure $callback, $item)
    {
        return $this->execute('convert', $interceptor, $callback, array_slice(func_get_args(), 2));
    }

    // ---------------------------------------

    protected function processConvert($interceptor, \Closure $callback, $arguments)
    {
        $orderItem = call_user_func_array($callback, $arguments);

        $this->eventManager->dispatch(
            'ess_sales_convert_quote_item_to_order_item',
            [
                'order_item' => $orderItem,
                'item' => $arguments[0],
            ]
        );

        return $orderItem;
    }

    //########################################
}