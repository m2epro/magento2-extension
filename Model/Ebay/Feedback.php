<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

/**
 * Class \Ess\M2ePro\Model\Ebay\Feedback
 */
class Feedback extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const ROLE_BUYER  = 'Buyer';
    const ROLE_SELLER = 'Seller';

    const TYPE_NEUTRAL  = 'Neutral';
    const TYPE_POSITIVE = 'Positive';
    const TYPE_NEGATIVE = 'Negative';

    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = null;

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Feedback');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->accountModel = null;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if ($this->accountModel === null) {
            $this->accountModel = $this->ebayFactory->getCachedObjectLoaded(
                'Account',
                $this->getData('account_id')
            );
        }

        return $this->accountModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $instance
     */
    public function setAccount(\Ess\M2ePro\Model\Account $instance)
    {
         $this->accountModel = $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    public function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    //########################################

    public function isNeutral()
    {
        return $this->getData('buyer_feedback_type') == self::TYPE_NEUTRAL;
    }

    public function isNegative()
    {
        return $this->getData('buyer_feedback_type') == self::TYPE_NEGATIVE;
    }

    public function isPositive()
    {
        return $this->getData('buyer_feedback_type') == self::TYPE_POSITIVE;
    }

    //########################################

    public function sendResponse($text, $type = self::TYPE_POSITIVE)
    {
        $paramsConnector = [
            'item_id'        => $this->getData('ebay_item_id'),
            'transaction_id' => $this->getData('ebay_transaction_id'),
            'text'           => $text,
            'type'           => $type,
            'target_user'    => $this->getData('buyer_name')
        ];

        $this->setData('last_response_attempt_date', $this->getHelper('Data')->getCurrentGmtDate())->save();

        try {
            $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObj->getVirtualConnector(
                'feedback',
                'add',
                'entity',
                $paramsConnector,
                null,
                null,
                $this->getAccount()
            );

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($connectorObj->getResponse()->getMessages()->hasErrorEntities()) {
                throw new \Ess\M2ePro\Model\Exception(
                    $connectorObj->getResponse()->getMessages()->getCombinedErrorsString()
                );
            }
        } catch (\Exception $e) {
            $synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
            $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
            $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_GENERAL);

            $synchronizationLog->addMessage(
                $this->getHelper('Module\Translation')->__($e->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($e);
            return false;
        }

        if (!isset($response['feedback_id'])) {
            return false;
        }

        $this->setData('seller_feedback_id', $response['feedback_id']);
        $this->setData('seller_feedback_type', $type);
        $this->setData('seller_feedback_text', $text);
        $this->setData('seller_feedback_date', $response['feedback_date']);

        $this->save();

        return true;
    }

    /**
     * @return \Ess\M2ePro\Model\Order|null
     */
    public function getOrder()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Order\Collection */
        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->getSelect()
            ->join(
                ['oi' => $this->activeRecordFactory->getObject('Order\Item')->getResource()->getMainTable()],
                '`oi`.`order_id` = `main_table`.`id`',
                []
            )
            ->join(
                ['eoi' => $this->activeRecordFactory->getObject('Ebay_Order_Item')->getResource()->getMainTable()],
                '`eoi`.`order_item_id` = `oi`.`id`',
                []
            );

        $collection->addFieldToFilter('account_id', $this->getData('account_id'));
        $collection->addFieldToFilter('eoi.item_id', $this->getData('ebay_item_id'));
        $collection->addFieldToFilter('eoi.transaction_id', $this->getData('ebay_transaction_id'));

        $collection->getSelect()->limit(1);

        $order = $collection->getFirstItem();

        return $order->getId() !== null ? $order : null;
    }

    //########################################
}
