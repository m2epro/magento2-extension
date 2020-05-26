<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue;

use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Issue\DataObject
 */
class DataObject extends \Ess\M2ePro\Model\AbstractModel
{
    const KEY_TITLE = 'title';
    const KEY_TEXT  = 'text';
    const KEY_TYPE  = 'type';
    const KEY_URL   = 'url';

    protected $type;
    protected $title;
    protected $text;
    protected $url;

    //########################################

    public function __construct(
        $type,
        $title,
        $text,
        $url,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->type  = $type;
        $this->title = $title;
        $this->text  = $text;
        $this->url   = $url;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getType()
    {
        return $this->type;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getUrl()
    {
        return $this->url;
    }

    //########################################

    public function isNotice()
    {
        return $this->getType() === Message::TYPE_NOTICE;
    }

    public function isSuccess()
    {
        return $this->getType() === Message::TYPE_SUCCESS;
    }

    public function isError()
    {
        return $this->getType() === Message::TYPE_ERROR;
    }

    public function isWarning()
    {
        return $this->getType() === Message::TYPE_WARNING;
    }

    //########################################
}
