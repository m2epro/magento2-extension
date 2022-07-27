<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\FinalFee;

class Update extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Order
{
    /** @var \Ess\M2ePro\Model\Ebay\Order\FinalFee\Fill */
    private $fillFee;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $logger;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    private $resultJsonFactory;

    /** @var \Ess\M2ePro\Model\Currency */
    private $currency;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Order\FinalFee\Fill $fillFee,
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Helper\Module\Exception $logger,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->fillFee           = $fillFee;
        $this->logger            = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->currency          = $currency;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id', false);
        if ($id === false) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'error' => $this->__('Wrong parameters.'),
                ]
            );
        }

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);
        if ($order->getChildObject()->getFinalFee()) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'data' => $this->formatSuccessResult($order->getChildObject()),
                ]
            );
        }

        /** @var \Ess\M2ePro\Model\Ebay\Account $account */
        $account = $order->getAccount()->getChildObject();
        if (!$account->getSellApiTokenSession()) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'sell_api_disabled' => true,
                    'error'             => $this->__('Sell Api token is missing.'),
                ]
            );
        }

        try {
            $this->fillFee->process($order->getChildObject(), $account);
        } catch (\Ess\M2ePro\Model\Ebay\Order\FinalFee\Exception\OldOrderId $e) {
            return $this->resultJsonFactory->create()->setData(
                [
                    'error' => $this->__($e->getMessage()),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->process($e);

            return $this->resultJsonFactory->create()->setData(
                [
                    'error' => $this->__('Unable to actualize final fee.'),
                ]
            );
        }

        return $this->resultJsonFactory->create()->setData(
            [
                'data' => $this->formatSuccessResult($order->getChildObject()),
            ]
        );
    }

    private function formatSuccessResult(\Ess\M2ePro\Model\Ebay\Order $order): ?string
    {
        return $order->getFinalFee()
            ? $this->currency->formatPrice($order->getCurrency(), $order->getFinalFee()) : null;
    }
}
