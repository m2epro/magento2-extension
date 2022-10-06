<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Cron implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'cron';

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    private $helperCron;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ess\M2ePro\Helper\Module\Cron $helperCron
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Model\Config\Manager $configManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Module\Cron $helperCron,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\Config\Manager $configManager
    ) {
        $this->storeManager = $storeManager;
        $this->helperCron = $helperCron;
        $this->registryManager = $registryManager;
        $this->configManager = $configManager;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return bool
     * @throws \Exception
     */
    public function isAllowed(): bool
    {
        if ($this->helperCron->getLastRun() === null) {
            return true;
        }

        if ($this->helperCron->isRunnerService() && $this->helperCron->isLastRunMoreThan(900)) {
            return true;
        }

        if ($this->helperCron->isRunnerMagento()) {
            $currentTimeStamp = \Ess\M2ePro\Helper\Date::createCurrentGmt()->getTimestamp();
            $lastTypeChange = $this->helperCron->getLastRunnerChange();
            $lastRun = $this->registryManager->getValue('/servicing/cron/last_run/');

            if (
                ($lastTypeChange === null ||
                    $currentTimeStamp > (int)\Ess\M2ePro\Helper\Date::createDateGmt($lastTypeChange)->format(
                        'U'
                    ) + 86400
                ) &&
                ($lastRun === null ||
                    $currentTimeStamp > (int)\Ess\M2ePro\Helper\Date::createDateGmt($lastRun)->format('U') + 86400)
            ) {
                $this->registryManager->setValue(
                    '/servicing/cron/last_run/',
                    \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
                );

                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequestData(): array
    {
        return [
            'base_url' => $this->storeManager->getStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID)
                                             ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, null),
        ];
    }

    /**
     * @param array $data
     */
    public function processResponseData(array $data): void
    {
        if (!isset($data['auth_key'])) {
            return;
        }

        $this->configManager->setGroupValue('/cron/service/', 'auth_key', $data['auth_key']);
    }
}
