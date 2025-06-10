<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Tag;

use Ess\M2ePro\Model\ResourceModel\Tag as ResourceModel;

class Entity extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Tag::class);
    }

    public function getErrorCode(): string
    {
        return $this->getDataByKey(ResourceModel::ERROR_CODE_FIELD);
    }

    public function setErrorCode(string $errorCode): void
    {
        $this->setData(ResourceModel::ERROR_CODE_FIELD, $errorCode);
    }

    public function getText(): string
    {
        return $this->getDataByKey(ResourceModel::TEXT_FIELD);
    }

    public function setText(string $text): void
    {
        $this->setData(ResourceModel::TEXT_FIELD, $text);
    }

    public function getCreateDate(): \DateTime
    {
        if (empty($this->getData(ResourceModel::CREATE_DATE_FIELD))) {
            throw new \Ess\M2ePro\Model\Exception\Logic(sprintf("Field '%s' must be set", ResourceModel::CREATE_DATE_FIELD));
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getData(ResourceModel::CREATE_DATE_FIELD)
        );
    }

    public function setCreateDate(\DateTime $createDate): void
    {
        $timeZone = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone());
        $createDate->setTimezone($timeZone);
        $this->setData(ResourceModel::CREATE_DATE_FIELD, $createDate->format('Y-m-d H:i:s'));
    }
}
