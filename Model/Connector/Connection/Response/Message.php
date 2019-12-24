<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection\Response;

/**
 * Class \Ess\M2ePro\Model\Connector\Connection\Response\Message
 */
class Message extends \Ess\M2ePro\Model\Response\Message
{
    const SENDER_KEY = 'sender';
    const CODE_KEY   = 'code';

    const SENDER_SYSTEM    = 'system';
    const SENDER_COMPONENT = 'component';

    //########################################

    protected $sender = null;
    protected $code   = null;

    //########################################

    public function initFromResponseData(array $responseData)
    {
        parent::initFromResponseData($responseData);

        $this->sender = $responseData[self::SENDER_KEY];
        $this->code   = $responseData[self::CODE_KEY];
    }

    public function initFromPreparedData($text, $type, $sender = null, $code = null)
    {
        parent::initFromPreparedData($text, $type);

        $this->sender = $sender;
        $this->code   = $code;
    }

    //########################################

    public function asArray()
    {
        return array_merge(parent::asArray(), [
            self::SENDER_KEY => $this->sender,
            self::CODE_KEY   => $this->code,
        ]);
    }

    //########################################

    public function getText()
    {
        return $this->text;
    }

    //########################################

    public function isSenderSystem()
    {
        return $this->sender == self::SENDER_SYSTEM;
    }

    public function isSenderComponent()
    {
        return $this->sender == self::SENDER_COMPONENT;
    }

    //########################################

    public function getCode()
    {
        return $this->code;
    }

    //########################################
}
