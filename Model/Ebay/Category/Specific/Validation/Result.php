<?php

namespace Ess\M2ePro\Model\Ebay\Category\Specific\Validation;

class Result extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const STATUS_INVALID = 0;
    public const STATUS_VALID = 1;

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result::class);
    }

    public function setListingProductId(int $listingProductId)
    {
        $this->setData('listing_product_id', $listingProductId);
    }

    public function getStatus(): int
    {
        return (int)$this->getData('status');
    }

    public function setStatus(int $status): void
    {
        $this->setData('status', $status);
    }

    public function setCreatedDate(\DateTime $date): void
    {
        $this->setData('create_date', $date->format('Y-m-d H:i:s'));
    }

    public function setUpdateDate(\DateTime $date)
    {
        $this->setData('update_date', $date->format('Y-m-d H:i:s'));
    }

    public function setInvalidStatus()
    {
        $this->setStatus(self::STATUS_INVALID);
    }

    public function setValidStatus()
    {
        $this->setStatus(self::STATUS_VALID);
    }

    public function addErrorMessage(string $errorMessage): void
    {
        $errorMessages = $this->getErrorMessages();
        $errorMessages[] = $errorMessage;

        $this->setErrorMessages($errorMessages);
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getErrorMessages(): array
    {
        return \Ess\M2ePro\Helper\Json::decode($this->getData('error_messages'));
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setErrorMessages(array $errorMessages): void
    {
        $this->setData('error_messages', \Ess\M2ePro\Helper\Json::encode($errorMessages));
    }
}
