<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Cron;

class Service extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAX_INACTIVE_TIME = 300;

    /** @var \Ess\M2ePro\Model\Config\Manager\Cache  */
    protected $cacheConfig;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->cacheConfig = $cacheConfig;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isConnectionMustBeClosed()
    {
        $forbidden = (bool)(int)$this->cacheConfig->getGroupValue('/cron/service/connection_closing/', 'forbidden');
        if (!$forbidden) {
            return true;
        }

        $forbiddenToDate = $this->cacheConfig->getGroupValue('/cron/service/connection_closing/', 'forbidden_to_date');
        if (!is_null($forbiddenToDate)) {

            $forbiddenToDate = new \DateTime($forbiddenToDate, new \DateTimeZone('UTC'));

            if ($this->getHelper('Data')->getCurrentGmtDate(true) > $forbiddenToDate->getTimestamp()) {
                return true;
            }
        }

        return false;
    }

    public function forbidClosingConnection()
    {
        $this->cacheConfig->setGroupValue('/cron/service/connection_closing/', 'forbidden', '1');

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify('+ 30 days');

        $this->cacheConfig->setGroupValue(
            '/cron/service/connection_closing/', 'forbidden_to_date', $date->format('Y-m-d H:i:s')
        );
    }

    //########################################
}