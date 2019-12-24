<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Action was not completed (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%

    const ACTION_ADD_DISPUTE   = 1;
    const ACTION_UPDATE_STATUS = 2;
    const ACTION_UPDATE_TRACK  = 3;

    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $helperFactory,
            $modelFactory,
            $data
        );
    }

    // ########################################

    public function process($action, $items, array $params = [])
    {
        $items = $this->prepareItems($items);
        $connector = null;

        switch ($action) {
            case self::ACTION_ADD_DISPUTE:
                $connector = 'Ebay_Connector_OrderItem_Add_Dispute';
                break;
            case self::ACTION_UPDATE_STATUS:
            case self::ACTION_UPDATE_TRACK:
                $connector = 'Ebay_Connector_OrderItem_Update_Status';
                break;
        }

        if ($connector === null) {
            return false;
        }

        return $this->processItems($items, $connector, $params);
    }

    // ########################################

    protected function processItems(array $items, $connectorName, array $params = [])
    {
        if (count($items) == 0) {
            return false;
        }

        /** @var $items \Ess\M2ePro\Model\Order\Item[] */

        foreach ($items as $item) {
            try {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

                $connector = $dispatcher->getCustomConnector($connectorName, $params);
                $connector->setOrderItem($item);

                $connector->process();
            } catch (\Exception $e) {
                $item->getOrder()->addErrorLog(
                    'Action was not completed (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%',
                    [
                        '!item_id' => $item->getChildObject()->getItemId(),
                        '!trn_id'  => $item->getChildObject()->getTransactionId(),
                        'msg'      => $e->getMessage()
                    ]
                );

                return false;
            }
        }

        return true;
    }

    // ########################################

    private function prepareItems($items)
    {
        !is_array($items) && $items = [$items];

        $preparedItems = [];

        foreach ($items as $item) {
            if ($item instanceof \Ess\M2ePro\Model\Order\Item) {
                $preparedItems[] = $item;
            } elseif (is_numeric($item)) {
                $preparedItems[] = $this->ebayFactory->getObjectLoaded('Order\Item', $item);
            }
        }

        return $preparedItems;
    }

    // ########################################
}
