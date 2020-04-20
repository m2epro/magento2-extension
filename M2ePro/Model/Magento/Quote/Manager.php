<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote;

/**
 * Class \Ess\M2ePro\Model\Magento\Quote\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Quote\Api\CartRepositoryInterface  */
    protected $quoteRepository;
    /** @var \Ess\M2ePro\Model\Magento\Backend\Model\Session\Quote  */
    protected $sessionQuote;
    /** @var \Magento\Quote\Model\QuoteManagement */
    protected $quoteManagement;
    /** @var \Magento\Checkout\Model\Session  */
    protected $checkoutSession;
    /** @var \Magento\Sales\Model\OrderFactory  */
    protected $orderFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Ess\M2ePro\Model\Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->quoteRepository = $quoteRepository;
        $this->sessionQuote    = $sessionQuote;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory    = $orderFactory;
    }

    //########################################

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getBlankQuote()
    {
        $this->clearQuoteSessionStorage();
        return $this->sessionQuote->getQuote();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|null|object
     * @throws \Ess\M2ePro\Model\Magento\Quote\FailDuringEventProcessing
     * @throws \Exception
     */
    public function submit(\Magento\Quote\Model\Quote $quote)
    {
        try {
            $order = $this->quoteManagement->submit($quote);
            return $order;
        } catch (\Exception $e) {
            $order = $this->orderFactory
                          ->create()
                          ->loadByIncrementIdAndStoreId(
                              $quote->getReservedOrderId(),
                              $quote->getStoreId()
                          );

            if ($order->getId()) {
                $this->helperFactory->getObject('Module\Exception')->process($e, false);
                throw new \Ess\M2ePro\Model\Magento\Quote\FailDuringEventProcessing(
                    $order,
                    $e->getMessage()
                );
            }
            // Remove ordered items from customer cart
            $quote->setIsActive(false)->save();
            throw $e;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function save(\Magento\Quote\Model\Quote $quote)
    {
        $this->quoteRepository->save($quote);
        return $quote;
    }

    public function replaceCheckoutQuote(\Magento\Quote\Model\Quote $quote)
    {
        $this->checkoutSession->replaceQuote($quote);
    }

    public function clearQuoteSessionStorage()
    {
        $this->sessionQuote->clearStorage();
    }

    //########################################
}
