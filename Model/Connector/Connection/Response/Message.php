<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection\Response;

class Message extends \Ess\M2ePro\Model\AbstractModel
{
    const TEXT_KEY   = 'text';
    const TYPE_KEY   = 'type';
    const SENDER_KEY = 'sender';
    const CODE_KEY   = 'code';

    const TYPE_ERROR   = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_NOTICE  = 'notice';

    const SENDER_SYSTEM    = 'system';
    const SENDER_COMPONENT = 'component';

    //########################################

    private $text   = '';
    private $type   = NULL;
    private $sender = NULL;
    private $code   = NULL;

    //########################################

    public function initFromResponseData(array $responseData)
    {
        $this->text   = $responseData[self::TEXT_KEY];
        $this->type   = $responseData[self::TYPE_KEY];
        $this->sender = $responseData[self::SENDER_KEY];
        $this->code   = $responseData[self::CODE_KEY];
    }

    public function initFromPreparedData($text, $type, $sender = NULL, $code = NULL)
    {
        $this->text   = $text;
        $this->type   = $type;
        $this->sender = $sender;
        $this->code   = $code;
    }

    public function initFromException(\Exception $exception)
    {
        $this->text = $exception->getMessage();
        $this->type = self::TYPE_ERROR;
    }

    //########################################

    public function asArray()
    {
        return array(
            self::TEXT_KEY   => $this->text,
            self::TYPE_KEY   => $this->type,
            self::SENDER_KEY => $this->sender,
            self::CODE_KEY   => $this->code,
        );
    }

    //########################################

    public function getText()
    {
        return $this->text;
    }

    //########################################

    public function isError()
    {
        return $this->type == self::TYPE_ERROR;
    }

    public function isWarning()
    {
        return $this->type == self::TYPE_WARNING;
    }

    public function isSuccess()
    {
        return $this->type == self::TYPE_SUCCESS;
    }

    public function isNotice()
    {
        return $this->type == self::TYPE_NOTICE;
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