<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

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
    private $accountModel = NULL;

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
    )
    {
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
        $this->_init('Ebay\Feedback');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->accountModel = NULL;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if (is_null($this->accountModel)) {
            $this->accountModel = $this->ebayFactory->getCachedObjectLoaded(
                'Account', $this->getData('account_id')
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
        $paramsConnector = array(
            'item_id'        => $this->getData('ebay_item_id'),
            'transaction_id' => $this->getData('ebay_transaction_id'),
            'text'           => $text,
            'type'           => $type,
            'target_user'    => $this->getData('buyer_name')
        );

        $this->setData('last_response_attempt_date', $this->getHelper('Data')->getCurrentGmtDate())->save();

        try {

            $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObj->getVirtualConnector('feedback', 'add', 'entity',
                                                                $paramsConnector, NULL, NULL,
                                                                $this->getAccount());

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

        } catch (\Exception $e) {
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
                array('oi' => $this->activeRecordFactory->getObject('Order\Item')->getResource()->getMainTable()),
                '`oi`.`order_id` = `main_table`.`id`',
                array()
            )
            ->join(
                array('eoi' => $this->activeRecordFactory->getObject('Ebay\Order\Item')->getResource()->getMainTable()),
                '`eoi`.`order_item_id` = `oi`.`id`',
                array()
            );

        $collection->addFieldToFilter('account_id', $this->getData('account_id'));
        $collection->addFieldToFilter('eoi.item_id', $this->getData('ebay_item_id'));
        $collection->addFieldToFilter('eoi.transaction_id', $this->getData('ebay_transaction_id'));

        $collection->getSelect()->limit(1);

        $order = $collection->getFirstItem();

        return !is_null($order->getId()) ? $order : NULL;
    }

    //########################################
}