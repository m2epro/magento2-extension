<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Response;

/**
 * Class \Ess\M2ePro\Model\Response\Message
 */
class Message extends \Ess\M2ePro\Model\AbstractModel
{
    const TEXT_KEY   = 'text';
    const TYPE_KEY   = 'type';

    const TYPE_ERROR   = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_NOTICE  = 'notice';

    //########################################

    protected $text = '';
    protected $type = null;

    //########################################

    public function initFromResponseData(array $responseData)
    {
        $this->text = $responseData[self::TEXT_KEY];
        $this->type = $responseData[self::TYPE_KEY];
    }

    public function initFromPreparedData($text, $type)
    {
        $this->text = $text;
        $this->type = $type;
    }

    public function initFromException(\Exception $exception)
    {
        $this->text = $exception->getMessage();
        $this->type = self::TYPE_ERROR;
    }

    //########################################

    public function asArray()
    {
        return [
            self::TEXT_KEY   => $this->text,
            self::TYPE_KEY   => $this->type,
        ];
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
}
