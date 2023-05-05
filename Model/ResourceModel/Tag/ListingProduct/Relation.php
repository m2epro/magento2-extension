<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct;

class Relation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const ID_FIELD = 'id';
    public const LISTING_PRODUCT_ID_FIELD = 'listing_product_id';
    public const TAG_ID_FIELD = 'tag_id';
    public const CREATE_DATE_FIELD = 'create_date';

    /**
     * @inerhitDoc
     */
    protected function _construct(): void
    {
        $this->_init('m2epro_listing_product_tag_relation', self::ID_FIELD);
    }

    /**
     * @param list<list<int>> $dataPackage
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertTags(array $dataPackage): void
    {
        $queryData = [];
        $createDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        foreach ($dataPackage as $listingProductId => $tagIds) {
            foreach ($tagIds as $tagId) {
                $queryData[] = [
                    self::LISTING_PRODUCT_ID_FIELD => $listingProductId,
                    self::TAG_ID_FIELD => $tagId,
                    self::CREATE_DATE_FIELD => $createDate
                ];
            }
        }

        if (!empty($queryData)) {
            $this->getConnection()->insertMultiple(
                $this->getMainTable(),
                $queryData
            );
        }
    }

    /**
     * @param list<list<int>> $dataPackage
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeTags(array $dataPackage): void
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from($this->getMainTable());

        $conditionExists = false;
        foreach ($dataPackage as $listingProductId => $tagIds) {
            foreach ($tagIds as $tagId) {
                $conditionExists = true;
                $select->orWhere(
                    self::LISTING_PRODUCT_ID_FIELD . " = {$listingProductId}"
                    . ' AND '
                    . self::TAG_ID_FIELD . " = {$tagId}"
                );
            }
        }

        if ($conditionExists) {
            $connection->query(
                $select->deleteFromSelect($this->getMainTable())
            );
        }
    }
}
