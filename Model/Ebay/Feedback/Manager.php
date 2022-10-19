<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Feedback;

class Manager
{
    private const ERROR_CODE_FEEDBACK_WAS_NOT_LEFT = 55;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory */
    private $ebayConnectorDispatcherFactory;
    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    private $synchronizationLog;

    /**
     * @param \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $ebayConnectorDispatcherFactory
     * @param \Ess\M2ePro\Model\Synchronization\LogFactory $synchronizationLogFactory
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\Ebay\Connector\DispatcherFactory $ebayConnectorDispatcherFactory,
        \Ess\M2ePro\Model\Synchronization\LogFactory $synchronizationLogFactory
    ) {
        $this->exceptionHelper = $exceptionHelper;
        $this->translationHelper = $translationHelper;
        $this->ebayConnectorDispatcherFactory = $ebayConnectorDispatcherFactory;

        $this->synchronizationLog = $synchronizationLogFactory->create();
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Feedback $feedback
     * @param string $text
     * @param string $type
     *
     * @return bool
     * @throws \Exception
     */
    public function sendResponse(
        \Ess\M2ePro\Model\Ebay\Feedback $feedback,
        string $text,
        string $type = \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE
    ): bool {
        $feedback->setLastResponseAttemptDate(\Ess\M2ePro\Helper\Date::createCurrentGmt());
        $connectorObj = $this->sendFeedbackToServer($feedback, $text, $type);

        if ($connectorObj === null) {
            $feedback->save();

            return false;
        }

        $response = $connectorObj->getResponseData();
        $messages = $connectorObj->getResponse()->getMessages();
        $this->handleResponseMessages($messages, $feedback);

        if ($messages->hasErrorEntities() || !isset($response['feedback_id'])) {
            $feedback->save();

            return false;
        }

        $feedback->setSellerFeedbackId($response['feedback_id']);
        $feedback->setSellerFeedbackType($type);
        $feedback->setSellerFeedbackText($text);
        $feedback->setSellerFeedbackDate(\Ess\M2ePro\Helper\Date::createDateGmt($response['feedback_date']));
        $feedback->save();

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Feedback $feedback
     * @param string $text
     * @param string $type
     *
     * @return \Ess\M2ePro\Model\Ebay\Connector\Dispatcher|null
     * @throws \Exception
     */
    private function sendFeedbackToServer(
        \Ess\M2ePro\Model\Ebay\Feedback $feedback,
        string $text,
        string $type
    ): ?\Ess\M2ePro\Model\Connector\Command\AbstractModel {
        $paramsConnector = [
            'item_id' => $feedback->getEbayItemId(),
            'transaction_id' => $feedback->getEbayTransactionId(),
            'text' => $text,
            'type' => $type,
            'target_user' => $feedback->getBuyerName(),
        ];

        try {
            $dispatcherObj = $this->ebayConnectorDispatcherFactory->create();
            /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObj */
            $connectorObj = $dispatcherObj->getVirtualConnector(
                'feedback',
                'add',
                'entity',
                $paramsConnector,
                null,
                null,
                $feedback->getAccount()
            );

            $dispatcherObj->process($connectorObj);

            return $connectorObj;
        } catch (\Throwable $e) {
            $this->exceptionHelper->process($e);

            return null;
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messages
     * @param \Ess\M2ePro\Model\Ebay\Feedback $feedback
     *
     * @return void
     */
    private function handleResponseMessages(
        \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messages,
        \Ess\M2ePro\Model\Ebay\Feedback $feedback
    ): void {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        foreach ($messages as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            if (
                $message->isError()
                && $message->getCode() == self::ERROR_CODE_FEEDBACK_WAS_NOT_LEFT
            ) {
                $feedback->setIsCriticalErrorReceived(true);
                continue;
            }

            $logType = $message->isError()
                ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->synchronizationLog->addMessage(
                $this->translationHelper->__($message->getText()),
                $logType
            );
        }
    }
}
