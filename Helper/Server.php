<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Server extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL = 86400;

    protected $primary;
    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Primary $primary,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->primary = $primary;
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getEndpoint()
    {
        if ($this->getCurrentIndex() != $this->getDefaultIndex()) {

            $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

            $interval = self::MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL;
            $switchingDateTime = $this->cacheConfig->getGroupValue('/server/location/','datetime_of_last_switching');

            if (is_null($switchingDateTime) || strtotime($switchingDateTime) + $interval <= $currentTimeStamp) {
                $this->setCurrentIndex($this->getDefaultIndex());
            }
        }

        return $this->getCurrentBaseUrl().'index.php';
    }

    public function switchEndpoint()
    {
        $previousIndex = $this->getCurrentIndex();
        $nextIndex = $previousIndex + 1;

        if (is_null($this->getBaseUrlByIndex($nextIndex))) {
            $nextIndex = 1;
        }

        if ($nextIndex == $previousIndex) {
            return false;
        }

        $this->setCurrentIndex($nextIndex);

        $this->cacheConfig->setGroupValue('/server/location/','datetime_of_last_switching',
                                        $this->getHelper('Data')->getCurrentGmtDate());

        return true;
    }

    //########################################

    public function getAdminKey()
    {
        return (string)$this->primary->getGroupValue('/server/', 'admin_key');
    }

    public function getApplicationKey()
    {
        return (string)$this->primary->getGroupValue('/server/', 'application_key');
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
        $index = (int)$this->primary->getGroupValue('/server/location/','default_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setDefaultIndex($index = 1);
        }

        return $index;
    }

    private function getCurrentIndex()
    {
        $index = (int)$this->cacheConfig->getGroupValue('/server/location/','current_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setCurrentIndex($index = $this->getDefaultIndex());
        }

        return $index;
    }

    // ---------------------------------------

    private function setDefaultIndex($index)
    {
        $this->primary->setGroupValue('/server/location/','default_index',$index);
    }

    private function setCurrentIndex($index)
    {
        $this->cacheConfig->setGroupValue('/server/location/','current_index',$index);
    }

    //########################################

    private function getMaxBaseUrlIndex()
    {
        $index = 1;

        for ($tempIndex=2; $tempIndex<100; $tempIndex++) {

            $tempBaseUrl = $this->getBaseUrlByIndex($tempIndex);

            if (!is_null($tempBaseUrl)) {
                $index = $tempIndex;
            } else {
                break;
            }
        }

        return $index;
    }

    private function getBaseUrlByIndex($index)
    {
        return $this->primary->getGroupValue('/server/location/'.$index.'/','baseurl');
    }

    private function getHostNameByIndex($index)
    {
        return $this->primary->getGroupValue('/server/location/'.$index.'/','hostname');
    }

    //########################################
}