<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Product;

use Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter as ResourceModel;

class AdvancedFilter extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel::class);
    }

    public function getModelNick(): string
    {
        return $this->getDataByKey(ResourceModel::COLUMN_MODEL_NICK);
    }

    public function setModelNick(string $modelNick): void
    {
        $this->setData(ResourceModel::COLUMN_MODEL_NICK, $modelNick);
    }

    public function getTitle(): string
    {
        return $this->getDataByKey(ResourceModel::COLUMN_TITLE);
    }

    public function setTitle(string $title): void
    {
        $this->setData(ResourceModel::COLUMN_TITLE, $title);
    }

    public function getConditionals(): string
    {
        return $this->getDataByKey(ResourceModel::COLUMN_CONDITIONALS);
    }

    public function setConditionals(string $conditionals): void
    {
        $this->setData(ResourceModel::COLUMN_CONDITIONALS, $conditionals);
    }

    public function getUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getData(ResourceModel::COLUMN_UPDATE_DATE)
        );
    }

    public function setUpdateDate(\DateTime $createDate): void
    {
        $timeZone = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone());
        $createDate->setTimezone($timeZone);
        $this->setData(ResourceModel::COLUMN_UPDATE_DATE, $createDate->format('Y-m-d H:i:s'));
    }

    public function getCreateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getData(ResourceModel::COLUMN_CREATE_DATE)
        );
    }

    public function setCreateDate(\DateTime $createDate): void
    {
        $timeZone = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone());
        $createDate->setTimezone($timeZone);
        $this->setData(ResourceModel::COLUMN_CREATE_DATE, $createDate->format('Y-m-d H:i:s'));
    }
}
