<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Action was not completed (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%

    const ACTION_ADD_DISPUTE   = 1;
    const ACTION_UPDATE_STATUS = 2;

    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $helperFactory,
            $modelFactory,
            $data
        );
    }

    // ########################################

    public function process($action, $items, array $params = array())
    {
        $items = $this->prepareItems($items);
        $connector = null;

        switch ($action) {
            case self::ACTION_ADD_DISPUTE:
                $connector = 'Ebay\Connector\OrderItem\Add\Dispute';
                break;
        }

        if (is_null($connector)) {
            return false;
        }

        return $this->processItems($items, $connector, $params);
    }

    // ########################################

    protected function processItems(array $items, $connectorName, array $params = array())
    {
        if (count($items) == 0) {
            return false;
        }

        /** @var $items \Ess\M2ePro\Model\Order\Item[] */

        foreach ($items as $item) {

            try {
                $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

                $connector = $dispatcher->getCustomConnector($connectorName, $params);
                $connector->setOrderItem($item);

                $connector->process();
            } catch (\Exception $e) {
                $item->getOrder()->addErrorLog(
                    'Action was not completed (Item: %item_id%, Transaction: %trn_id%). Reason: %msg%', array(
                        '!item_id' => $item->getChildObject()->getItemId(),
                        '!trn_id'  => $item->getChildObject()->getTransactionId(),
                        'msg'      => $e->getMessage()
                    )
                );

                return false;
            }
        }

        return true;
    }

    // ########################################

    private function prepareItems($items)
    {
        !is_array($items) && $items = array($items);

        $preparedItems = array();

        foreach ($items as $item) {
            if ($item instanceof \Ess\M2ePro\Model\Order\Item) {
                $preparedItems[] = $item;
            } else if (is_numeric($item)) {
                $preparedItems[] = $this->ebayFactory->getObjectLoaded('Order\Item', $item);
            }
        }

        return $preparedItems;
    }

    // ########################################
}