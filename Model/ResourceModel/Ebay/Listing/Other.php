<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

use Ess\M2ePro\Helper\Component\Ebay as EbayHelper;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_other', 'listing_other_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function resetEntities()
    {
        $listingOther = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Other');
        $ebayListingOther = $this->activeRecordFactory->getObject('Ebay_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $itemIds = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $ebayListingOther->setData($row);

            $listingOther->setChildObject($ebayListingOther);
            $ebayListingOther->setParentObject($listingOther);
            $itemIds[] = $ebayListingOther->getItemId();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_item');
        foreach (array_chunk($itemIds, 1000) as $chunkItemIds) {
            $this->resourceConnection->getConnection() ->delete($tableName, ['item_id IN (?)' => $chunkItemIds]);
        }

        foreach ($this->parentFactory->getObject(EbayHelper::NICK, 'Account')->getCollection() as $account) {
            $account->getChildObject()->setData('other_listings_last_synchronization', null)->save();
        }
    }

    //########################################
}
