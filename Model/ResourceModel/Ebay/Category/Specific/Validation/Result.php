<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation;

class Result extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected function _construct()
    {
        $this->_init('m2epro_ebay_category_specific_validation_result', 'id');
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result $object
     *
     * @return \Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $now = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $object->setUpdateDate($now);

        if ($object->isObjectNew()) {
            $object->setCreatedDate(\Ess\M2ePro\Helper\Date::createCurrentGmt());
        }

        return parent::save($object);
    }
}
