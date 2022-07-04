<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Server
{
    private const MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL = 600;

    /** @var \Ess\M2ePro\Helper\Module */
    protected $helperModule;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;
    /** @var \Ess\M2ePro\Helper\Date */
    private $dateHelper;

    /**
     * @param \Ess\M2ePro\Helper\Date $dateHelper
     * @param \Ess\M2ePro\Helper\Module $helperModule
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Helper\Date $dateHelper,
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->helperModule = $helperModule;
        $this->registry = $registry;
        $this->config = $config;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        if ($this->getCurrentIndex() !== $this->getDefaultIndex()) {
            $currentTimeStamp = $this->dateHelper->getCurrentGmtDate(true);

            $interval = self::MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL;

            $switchingDateTime = $this->registry->getValue(
                '/server/location/datetime_of_last_switching'
            );

            if (
                $switchingDateTime === null
                || (int)$this->dateHelper->createGmtDateTime($switchingDateTime)->format('U') + $interval
                <= $currentTimeStamp
            ) {
                $this->setCurrentIndex($this->getDefaultIndex());
            }
        }

        return $this->getCurrentBaseUrl() . '/index.php';
    }

    /**
     * @return bool
     */
    public function switchEndpoint(): bool
    {
        $previousIndex = $this->getCurrentIndex();
        $nextIndex = $previousIndex + 1;

        if ($this->getBaseUrlByIndex($nextIndex) === null) {
            $nextIndex = 1;
        }

        if ($nextIndex === $previousIndex) {
            return false;
        }

        $this->setCurrentIndex($nextIndex);

        $this->registry->setValue(
            '/server/location/datetime_of_last_switching',
            $this->dateHelper->getCurrentGmtDate()
        );

        return true;
    }

    /**
     * @return string
     */
    public function getApplicationKey(): string
    {
        return (string)$this->config->getGroupValue('/server/', 'application_key');
    }

    /**
     * @return string|null
     */
    public function getCurrentHostName(): ?string
    {
        return $this->getHostNameByIndex($this->getCurrentIndex());
    }

    /**
     * @return string
     */
    private function getCurrentBaseUrl(): string
    {
        $dbBaseUrl = $this->getBaseUrlByIndex($this->getCurrentIndex());
        $dbBaseUrl = str_replace('index.php', '', $dbBaseUrl);

        return rtrim($dbBaseUrl, '/');
    }

    /**
     * @return int
     */
    private function getDefaultIndex(): int
    {
        $index = (int)$this->config->getGroupValue('/server/location/', 'default_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setDefaultIndex($index = 1);
        }

        return $index;
    }

    /**
     * @return int
     */
    private function getCurrentIndex(): int
    {
        $index = (int)$this->config->getGroupValue('/server/location/', 'current_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setCurrentIndex($index = $this->getDefaultIndex());
        }

        return $index;
    }

    /**
     * @param int $index
     *
     * @return void
     */
    private function setDefaultIndex(int $index): void
    {
        $this->config->setGroupValue('/server/location/', 'default_index', $index);
    }

    /**
     * @param int $index
     *
     * @return void
     */
    private function setCurrentIndex(int $index): void
    {
        $this->config->setGroupValue('/server/location/', 'current_index', $index);
    }

    /**
     * @return int
     */
    private function getMaxBaseUrlIndex(): int
    {
        $index = 1;

        for ($tempIndex = 2; $tempIndex < 100; $tempIndex++) {
            $tempBaseUrl = $this->getBaseUrlByIndex($tempIndex);

            if ($tempBaseUrl !== null) {
                $index = $tempIndex;
            } else {
                break;
            }
        }

        return $index;
    }

    /**
     * @param int $index
     *
     * @return string|null
     */
    private function getBaseUrlByIndex(int $index): ?string
    {
        return $this->config->getGroupValue('/server/location/' . $index . '/', 'baseurl');
    }

    /**
     * @param int $index
     *
     * @return string|null
     */
    private function getHostNameByIndex(int $index): ?string
    {
        return $this->config->getGroupValue('/server/location/' . $index . '/', 'hostname');
    }
}
