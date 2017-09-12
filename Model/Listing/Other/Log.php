<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Other;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Other\Log getResource()
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    const ACTION_UNKNOWN = 1;
    const _ACTION_UNKNOWN = 'System';

    const ACTION_ADD_ITEM = 4;
    const _ACTION_ADD_ITEM = 'Add new Item';
    const ACTION_DELETE_ITEM = 5;
    const _ACTION_DELETE_ITEM = 'Delete existing Item';

    const ACTION_MAP_ITEM = 6;
    const _ACTION_MAP_ITEM = 'Map Item to Magento Product';

    const ACTION_UNMAP_ITEM = 8;
    const _ACTION_UNMAP_ITEM = 'Unmap Item from Magento Product';

    const ACTION_MOVE_ITEM = 7;
    const _ACTION_MOVE_ITEM = 'Move to existing M2E Pro Listing';

    const ACTION_CHANNEL_CHANGE = 18;
    const _ACTION_CHANNEL_CHANGE = 'Change Item on Channel';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Other\Log');
    }

    //########################################

    public function addProductMessage($listingOtherId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL)
    {
        $dataForAdd = $this->makeDataForAdd($listingOtherId,
                                            $initiator,
                                            $actionId,
                                            $action,
                                            $description,
                                            $type,
                                            $priority);

        $this->createMessage($dataForAdd);
    }

    //########################################

    public function clearMessages($listingOtherId = NULL)
    {
        $filters = array();

        if (!is_null($listingOtherId)) {
            $filters['listing_other_id'] = $listingOtherId;
        }
        if (!is_null($this->componentMode)) {
            $filters['component_mode'] = $this->componentMode;
        }

        $this->getResource()->clearMessages($filters);
    }

    //########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = $this->parentFactory->getObjectLoaded(
            $this->getComponentMode(), 'Listing\Other', $dataForAdd['listing_other_id']
        );

        $dataForAdd['account_id']     = $listingOther->getAccountId();
        $dataForAdd['marketplace_id'] = $listingOther->getMarketplaceId();
        $dataForAdd['title']          = $listingOther->getChildObject()->getTitle();

        if ($this->componentMode == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $dataForAdd['identifier'] = $listingOther->getChildObject()->getItemId();
        }

        if ($this->componentMode == \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            $dataForAdd['identifier'] = $listingOther->getChildObject()->getGeneralId();
        }

        $dataForAdd['component_mode'] = $this->getComponentMode();

        $this->activeRecordFactory->getObject('Listing\Other\Log')
            ->setData($dataForAdd)
            ->save()
            ->getId();
    }

    protected function makeDataForAdd($listingOtherId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = array();

        if (!is_null($listingOtherId)) {
            $dataForAdd['listing_other_id'] = (int)$listingOtherId;
        } else {
            $dataForAdd['listing_other_id'] = NULL;
        }

        $dataForAdd['initiator'] = $initiator;

        if (!is_null($actionId)) {
            $dataForAdd['action_id'] = (int)$actionId;
        } else {
            $dataForAdd['action_id'] = $this->getResource()->getNextActionId();
        }

        if (!is_null($action)) {
            $dataForAdd['action'] = (int)$action;
        } else {
            $dataForAdd['action'] = self::ACTION_UNKNOWN;
        }

        if (!is_null($description)) {
            $dataForAdd['description'] = $description;
        } else {
            $dataForAdd['description'] = NULL;
        }

        if (!is_null($type)) {
            $dataForAdd['type'] = (int)$type;
        } else {
            $dataForAdd['type'] = self::TYPE_NOTICE;
        }

        if (!is_null($priority)) {
            $dataForAdd['priority'] = (int)$priority;
        } else {
            $dataForAdd['priority'] = self::PRIORITY_LOW;
        }

        $dataForAdd['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);

        return $dataForAdd;
    }

    //########################################
}