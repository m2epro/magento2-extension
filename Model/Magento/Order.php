<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento;

class Order extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var $quote \Magento\Quote\Model\Quote */
    protected $quote;

    protected $quoteManagement;

    /** @var $order \Magento\Sales\Model\Order */
    protected $order;

    //########################################

    public function __construct(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    //########################################

    public function buildOrder()
    {
        $this->createOrder();
    }

    private function createOrder()
    {
        try {
            $this->order = $this->placeOrder();
        } catch (\Exception $e) {
            // Remove ordered items from customer cart
            // ---------------------------------------
            $this->quote->setIsActive(false)->save();
            // ---------------------------------------
            throw $e;
        }

        // Remove ordered items from customer cart
        // ---------------------------------------
        $this->quote->setIsActive(false)->save();
        // ---------------------------------------
    }

    private function placeOrder()
    {
        return $this->quoteManagement->submit($this->quote);
    }

    //########################################
}