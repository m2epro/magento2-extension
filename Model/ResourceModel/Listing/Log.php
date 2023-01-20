<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

class Log extends \Ess\M2ePro\Model\ResourceModel\Log\AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $logHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Log $logHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct(
            $dbStructureHelper,
            $helperFactory,
            $activeRecordFactory,
            $parentFactory,
            $context,
            $connectionName
        );

        $this->logHelper = $logHelper;
    }

    protected function _construct(): void
    {
        $this->_init('m2epro_listing_log', 'id');
    }

    /**
     * @return string
     */
    public function getConfigGroupSuffix(): string
    {
        return 'listings';
    }

    // ----------------------------------------

    public function updateListingTitle($listingId, $title): bool
    {
        if ($title == '') {
            return false;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['listing_title' => $title],
            ['listing_id = ?' => (int)$listingId]
        );

        return true;
    }

    public function updateProductTitle($productId, $title): bool
    {
        if ($title == '') {
            return false;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['product_title' => $title],
            ['product_id = ?' => (int)$productId]
        );

        return true;
    }

    public function getStatusByActionId($listingLog, $actionId)
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $listingLog */
        $collection = $listingLog->getCollection();
        $collection->addFieldToFilter('action_id', $actionId);
        $collection->addOrder('type');
        $resultType = $collection->getFirstItem()->getData('type');

        if (empty($resultType)) {
            throw new \Exception('Logs action ID does not exist.');
        }

        return $this->logHelper->getStatusByResultType($resultType);
    }
}
