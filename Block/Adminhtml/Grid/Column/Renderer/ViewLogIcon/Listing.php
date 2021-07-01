<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing
 */
class Listing extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    protected function getAvailableActions()
    {
        $translator = $this->getHelper('Module\Translation');

        return [
            Log::ACTION_LIST_PRODUCT_ON_COMPONENT   => $translator->__('List'),
            Log::ACTION_RELIST_PRODUCT_ON_COMPONENT => $translator->__('Relist'),
            Log::ACTION_REVISE_PRODUCT_ON_COMPONENT => $translator->__('Revise'),
            Log::ACTION_STOP_PRODUCT_ON_COMPONENT   => $translator->__('Stop'),
            Log::ACTION_REMAP_LISTING_PRODUCT       => $translator->__('Relink'),
            Log::ACTION_STOP_AND_REMOVE_PRODUCT     => $translator->__('Stop on Channel / Remove from Listing'),
            Log::ACTION_CHANNEL_CHANGE              => $translator->__('Channel Change')
        ];
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $listingProductId = (int)$row->getData('id');
        $availableActionsId = array_keys($this->getAvailableActions());

        $connection = $this->resourceConnection->getConnection();

        // Get last messages
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
                ['action_id','action','type','description','create_date','initiator','listing_product_id']
            )
            ->where('`action_id` IS NOT NULL')
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        if ($this->isVariationParent()) {
            $dbSelect->where('`listing_product_id` = ? OR `parent_listing_product_id` = ?', $listingProductId);
        } else {
            $dbSelect->where('`listing_product_id` = ?', $listingProductId);
        }

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        return $this->getLastActions($listingProductId, $logs);
    }

    //########################################

    protected function getLastActions($listingProductId, $logs)
    {
        $summary = $this->createBlock('Listing_Log_Grid_LastActions')->setData([
            'entity_id'           => $listingProductId,
            'logs'                => $logs,
            'available_actions'   => $this->getAvailableActions(),
            'view_help_handler'   => "{$this->getJsHandler()}.viewItemHelp",
            'hide_help_handler'   => "{$this->getJsHandler()}.hideItemHelp"
        ]);

        return $summary->toHtml();
    }

    //########################################

    protected function getJsHandler()
    {
        if ($this->hasData('jsHandler')) {
            return $this->getData('jsHandler');
        }

        return 'ListingGridObj';
    }

    protected function isVariationParent()
    {
        if ($this->hasData('is_variation_parent')) {
            return $this->getData('is_variation_parent');
        }

        return false;
    }

    //########################################
}
