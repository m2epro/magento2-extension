<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\FinalFee;

class Fill
{
    private const RESULT_CODE_OLD_ORDER_ID = 1;

    /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Get\FinalFeeFactory */
    private $commandFactory;

    public function __construct(\Ess\M2ePro\Model\Ebay\Connector\Order\Get\FinalFeeFactory $commandFactory)
    {
        $this->commandFactory = $commandFactory;
    }

    public function process(\Ess\M2ePro\Model\Ebay\Order $order, \Ess\M2ePro\Model\Ebay\Account $account): void
    {
        $connector = $this->commandFactory->create([
                'params' => [
                    'account_server_hash' => $account->getServerHash(),
                    'order_id'            => $order->getEbayOrderId(),
                ],
            ]
        );

        $connector->process();

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Get\FinalFee\Response $response */
        $response = $connector->getResponseData();

        if ($connector->getResponse()->isResultSuccess()) {
            if ($response->hasFinal()) {
                $order->setFinalFee($response->getFinal());
                $order->save();
            }

            return;
        }

        foreach ($connector->getResponse()->getMessages()->getEntities() as $message) {
            if (
                $message->isWarning()
                && $message->isSenderSystem()
                && $message->getCode() === self::RESULT_CODE_OLD_ORDER_ID
            ) {
                throw new \Ess\M2ePro\Model\Ebay\Order\FinalFee\Exception\OldOrderId($message->getText());
            }
        }

        throw new \Ess\M2ePro\Model\Exception(
            'Unable to update final fee.',
            [
                'response_messages' => $connector->getResponse()->getMessages()->getEntitiesAsArrays(),
                'account_id'        => $account->getId(),
                'order_id'          => $order->getId(),
            ]
        );
    }
}
