<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Amazon\Repricing\ResponseMessage as RepricingResponseMessage;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Amazon\Account\Repricing as AccountRepricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var Account $account */
    private $account = null;

    /** @var \Ess\M2ePro\Model\Synchronization\Log $synchronizationLog */
    protected $synchronizationLog = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;
    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     * @throws Logic
     */
    protected function getAmazonAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    /**
     * @return AccountRepricing
     */
    protected function getAmazonAccountRepricing()
    {
        return $this->getAmazonAccount()->getRepricing();
    }

    //########################################

    protected function processErrorMessages($response)
    {
        if (empty($response['messages'])) {
            return;
        }

        foreach ($response['messages'] as $messageData) {
            $message = new RepricingResponseMessage(
                $messageData['text'] ?? '',
                $messageData['type'] ?? RepricingResponseMessage::TYPE_WARNING,
                (int)($messageData['code'] ?? RepricingResponseMessage::DEFAULT_CODE)
            );

            if (!$message->isError()) {
                continue;
            }

            $errorText = $message->getText();

            if (
                $message->getCode() === RepricingResponseMessage::NOT_FOUND_ACCOUNT_CODE
            ) {
                $errorText = 'Repricer account is invalid.';

                if (!$this->getAmazonAccountRepricing()->isInvalid()) {
                    $this->getAmazonAccountRepricing()
                         ->markAsInvalid()
                         ->save();

                    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent $cache */
                    $cache = $this->getHelper('Data_Cache_Permanent');
                    $cache->removeValue(\Ess\M2ePro\Model\Amazon\Repricing\Issue\InvalidToken::CACHE_KEY);
                }
            }

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($errorText),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            $exception = new \Ess\M2ePro\Model\Exception($errorText);
            $this->getHelper('Module\Exception')->process($exception);
        }
    }

    //########################################

    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_REPRICING);

        return $this->synchronizationLog;
    }

    //########################################
}
