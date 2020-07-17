<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var string */
    protected $_identifier;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return bool
     * @throws \Exception
     */
    public function isEnabled()
    {
        return $this->getFromDate() !== null && $this->getToDate() !== null;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isInProgress()
    {
        return $this->isEnabled() && $this->getCurrentFromDate() !== null;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isCompleted()
    {
        return $this->isInProgress() &&
               $this->getCurrentFromDate()->getTimestamp() == $this->getToDate()->getTimestamp();
    }

    //----------------------------------------

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getFromDate()
    {
        $date = $this->getSettings('from_date');
        if ($date === null) {
            return $date;
        }

        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getToDate()
    {
        $date = $this->getSettings('to_date');
        if ($date === null) {
            return $date;
        }

        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getCurrentFromDate()
    {
        $date = $this->getSettings('current_from_date');
        if ($date === null) {
            return $date;
        }

        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @return string|null
     */
    public function getJobToken()
    {
        return $this->getSettings('job_token');
    }

    //----------------------------------------

    /**
     * @param string|false $fromDate
     * @param string|false $toDate
     */
    public function setFromToDates($fromDate = false, $toDate = false)
    {
        $this->validate($fromDate, $toDate);

        $this->setSettings('from_date', $fromDate);
        $this->setSettings('to_date', $toDate);
    }

    /**
     * @param string $fromDate
     * @param string $toDate
     * @return bool
     */
    public function validate($fromDate, $toDate)
    {
        $from = new \DateTime($fromDate, new \DateTimeZone('UTC'));
        $to   = new \DateTime($toDate, new \DateTimeZone('UTC'));

        if ($from->getTimestamp() > $to->getTimestamp()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('From date is bigger than To date.');
        }

        $now = $this->getHelper('Data')->getCurrentGmtDate(true);
        if ($from->getTimestamp() > $now || $to->getTimestamp() > $now) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Dates you provided are bigger than current.');
        }

        if ($from->diff($to)->days > 30) {
            throw new \Ess\M2ePro\Model\Exception\Logic('From - to interval provided is too big. (Max: 30 days)');
        }

        $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDate->modify('-90 days');

        if ($from->getTimestamp() < $minDate->getTimestamp()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('From date provided is too old. (Max: 90 days)');
        }

        return true;
    }

    /**
     * @param string $currentFromDate
     */
    public function setCurrentFromDate($currentFromDate)
    {
        $this->setSettings('current_from_date', $currentFromDate);
    }

    /**
     * @param string|null $jobToken
     */
    public function setJobToken($jobToken)
    {
        $this->setSettings('job_token', $jobToken);
    }

    //----------------------------------------

    public function clear()
    {
        $this->removeSettings();
    }

    //########################################

    public function setIdentifier($id)
    {
        $this->_identifier = $id;
        return $this;
    }

    public function getIdentifier()
    {
        return $this->_identifier;
    }

    //----------------------------------------

    public function setIdentifierByAccount(\Ess\M2ePro\Model\Account $account)
    {
        return $this->setIdentifier($account->getChildObject()->getUserId());
    }

    //########################################

    protected function getSettings($key = null)
    {
        $value = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson("/ebay/orders/upload_by_user/{$this->_identifier}/");

        if ($key === null) {
            return $value;
        }

        return isset($value[$key]) ? $value[$key] : null;
    }

    protected function setSettings($key, $value)
    {
        $settings = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson("/ebay/orders/upload_by_user/{$this->_identifier}/");
        $settings[$key] = $value;

        $this->getHelper('Module')->getRegistry()->setValue(
            "/ebay/orders/upload_by_user/{$this->_identifier}/",
            $settings
        );
    }

    protected function removeSettings()
    {
        $this->getHelper('Module')->getRegistry()->deleteValue("/ebay/orders/upload_by_user/{$this->_identifier}/");
    }

    //########################################
}
