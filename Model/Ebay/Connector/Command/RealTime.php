<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Command;

abstract class RealTime extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace|null
     */
    protected $marketplace = NULL;

    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = NULL;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params
    )
    {
        $this->marketplace = (!is_null($marketplace->getId())) ? $marketplace : NULL;
        $this->account = (!is_null($account->getId())) ? $account : NULL;

        parent::__construct($helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function buildRequestInstance()
    {
        $request = parent::buildRequestInstance();

        $requestData = $request->getData();

        if (!is_null($this->marketplace)) {
            $requestData['marketplace'] = $this->marketplace->getNativeId();
        }
        if (!is_null($this->account)) {
            $requestData['account'] = $this->account->getChildObject()->getServerHash();
        }

        $request->setData($requestData);

        return $request;
    }

    // ########################################

    public static function ebayTimeToString($time)
    {
        return (string)self::getEbayDateTimeObject($time)->format('Y-m-d H:i:s');
    }

    public static function ebayTimeToTimeStamp($time)
    {
        return (int)self::getEbayDateTimeObject($time)->format('U');
    }

    // -----------------------------------------

    private static function getEbayDateTimeObject($time)
    {
        $dateTime = NULL;

        if ($time instanceof \DateTime) {
            $dateTime = clone $time;
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
        } else {
            is_int($time) && $time = '@'.$time;
            $dateTime = new \DateTime($time, new \DateTimeZone('UTC'));
        }

        if (is_null($dateTime)) {
            throw new \Ess\M2ePro\Model\Exception('eBay DateTime object is null');
        }

        return $dateTime;
    }

    // ########################################
}