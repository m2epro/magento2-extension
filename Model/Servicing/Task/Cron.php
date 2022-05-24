<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Cron
 */
class Cron extends \Ess\M2ePro\Model\Servicing\Task
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Eav\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    ) {
        $this->helperData = $helperData;
        parent::__construct(
            $config,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'cron';
    }

    //########################################

    /**
     * @return bool
     */
    public function isAllowed()
    {
        $helper = $this->getHelper('Module\Cron');

        if ($this->getInitiator() === \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) {
            return true;
        }

        if ($helper->getLastRun() === null) {
            return true;
        }

        if ($helper->isRunnerService() && $helper->isLastRunMoreThan(900)) {
            return true;
        }

        if ($helper->isRunnerMagento()) {
            $currentTimeStamp = $this->helperData->getCurrentGmtDate(true);
            $lastTypeChange = $helper->getLastRunnerChange();
            $lastRun = $this->getHelper('Module')->getRegistry()->getValue('/servicing/cron/last_run/');

            if (($lastTypeChange === null ||
                    $currentTimeStamp > (int)$this->helperData->createGmtDateTime($lastTypeChange)->format('U') + 86400
                ) &&
                ($lastRun === null ||
                    $currentTimeStamp > (int)$this->helperData->createGmtDateTime($lastRun)->format('U') + 86400)
            ) {
                $this->getHelper('Module')->getRegistry()->setValue(
                    '/servicing/cron/last_run/',
                    $this->helperData->getCurrentGmtDate()
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
    public function getRequestData()
    {
        return [
            'base_url' => $this->storeManager->getStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID)
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, null)
        ];
    }

    public function processResponseData(array $data)
    {
        if (!isset($data['auth_key'])) {
            return;
        }

        $this->getHelper('Module')->getConfig()
                                  ->setGroupValue('/cron/service/', 'auth_key', $data['auth_key']);
    }

    //########################################
}
