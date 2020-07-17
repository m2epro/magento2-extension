<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\Server
 */
class Server extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL = 86400;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getEndpoint()
    {
        if ($this->getCurrentIndex() != $this->getDefaultIndex()) {
            $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

            $interval = self::MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL;

            $switchingDateTime = $this->getHelper('Module')->getRegistry()->getValue(
                '/server/location/datetime_of_last_switching'
            );

            if ($switchingDateTime === null || strtotime($switchingDateTime) + $interval <= $currentTimeStamp) {
                $this->setCurrentIndex($this->getDefaultIndex());
            }
        }

        return $this->getCurrentBaseUrl().'index.php';
    }

    public function switchEndpoint()
    {
        $previousIndex = $this->getCurrentIndex();
        $nextIndex = $previousIndex + 1;

        if ($this->getBaseUrlByIndex($nextIndex) === null) {
            $nextIndex = 1;
        }

        if ($nextIndex == $previousIndex) {
            return false;
        }

        $this->setCurrentIndex($nextIndex);

        $this->getHelper('Module')->getRegistry()->setValue(
            '/server/location/datetime_of_last_switching',
            $this->getHelper('Data')->getCurrentGmtDate()
        );

        return true;
    }

    //########################################

    public function getApplicationKey()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/server/', 'application_key');
    }

    //########################################

    public function getCurrentBaseUrl()
    {
        return $this->getBaseUrlByIndex($this->getCurrentIndex());
    }

    public function getCurrentHostName()
    {
        return $this->getHostNameByIndex($this->getCurrentIndex());
    }

    // ---------------------------------------

    private function getDefaultIndex()
    {
        $index = (int)$this->getHelper('Module')->getConfig()->getGroupValue('/server/location/', 'default_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setDefaultIndex($index = 1);
        }

        return $index;
    }

    private function getCurrentIndex()
    {
        $index = (int)$this->getHelper('Module')->getConfig()->getGroupValue('/server/location/', 'current_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setCurrentIndex($index = $this->getDefaultIndex());
        }

        return $index;
    }

    // ---------------------------------------

    private function setDefaultIndex($index)
    {
        $this->getHelper('Module')->getConfig()->setGroupValue('/server/location/', 'default_index', $index);
    }

    private function setCurrentIndex($index)
    {
        $this->getHelper('Module')->getConfig()->setGroupValue('/server/location/', 'current_index', $index);
    }

    //########################################

    private function getMaxBaseUrlIndex()
    {
        $index = 1;

        for ($tempIndex=2; $tempIndex<100; $tempIndex++) {
            $tempBaseUrl = $this->getBaseUrlByIndex($tempIndex);

            if ($tempBaseUrl !== null) {
                $index = $tempIndex;
            } else {
                break;
            }
        }

        return $index;
    }

    private function getBaseUrlByIndex($index)
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/server/location/'.$index.'/', 'baseurl');
    }

    private function getHostNameByIndex($index)
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/server/location/'.$index.'/', 'hostname');
    }

    //########################################
}
