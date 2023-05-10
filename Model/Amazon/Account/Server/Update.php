<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Server;

class Update
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
    private $dispatcher;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher
     */
    public function __construct(\Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Amazon\Account $account,
        string $oAuthCode,
        string $merchantId
    ): void {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Update\EntityRequester $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'account',
            'update',
            'entityRequester',
            [
                'account_server_hash' => $account->getServerHash(),
                'oauth_code' => $oAuthCode,
                'marketplace_id' => $account->getMarketplace()->getNativeId(),
                'merchant_id' => $merchantId,
            ]
        );

        $this->dispatcher->process($connectorObj);

        $messages = $connectorObj->getResponse()->getMessages();
        if ($messages->hasErrorEntities()) {
            $errorMessages = $messages->getErrorEntities();
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = reset($errorMessages);

            throw new \Ess\M2ePro\Model\Exception((string)$message->getText());
        }

        $responseData = $connectorObj->getResponseData();

        $newInfo = $responseData['info'];
        if ($this->isNewInfoObtain($newInfo, (array)$account->getDecodedInfo())) {
            $account->addData(
                [
                    'info' => json_encode($newInfo),
                ]
            );
            $account->save();
        }
    }

    // ----------------------------------------

    /**
     * @param array $newInfo
     * @param array $oldInfo
     *
     * @return bool
     */
    private function isNewInfoObtain(array $newInfo, array $oldInfo): bool
    {
        return $newInfo !== $oldInfo;
    }
}
