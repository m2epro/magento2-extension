<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced;

use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced\Filter as FilterResource;

class Filter extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public function _construct()
    {
        $this->_init(FilterResource::class);
    }

    public function setListingAutoAdvancedFilterId(int $value): self
    {
        $this->setData(FilterResource::COLUMN_LISTING_AUTO_ADVANCED_FILTER_ID, $value);

        return $this;
    }

    public function setAddingProductTypeTemplateId(?int $value): self
    {
        $this->setData(FilterResource::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID, $value);

        return $this;
    }

    public function getAddingProductTypeTemplateId(): ?int
    {
        $value = $this->getData(FilterResource::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID);
        if (empty($value)) {
            return null;
        }

        return (int)$value;
    }
}
