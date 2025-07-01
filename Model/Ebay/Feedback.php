<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

class Feedback extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const ROLE_BUYER = 'Buyer';
    public const ROLE_SELLER = 'Seller';

    public const TYPE_NEUTRAL = 'Neutral';
    public const TYPE_POSITIVE = 'Positive';
    public const TYPE_NEGATIVE = 'Negative';

    /** @var \Ess\M2ePro\Model\Account */
    private $accountModel = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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

        $this->ebayFactory = $ebayFactory;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Feedback::class);
    }

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->accountModel = null;

        return $temp;
    }

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

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account|\Ess\M2ePro\Model\Ebay\Account|\Ess\M2ePro\Model\Walmart\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    /**
     * @return bool
     */
    public function isNeutral(): bool
    {
        return $this->getBuyerFeedbackType() == self::TYPE_NEUTRAL;
    }

    /**
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->getBuyerFeedbackType() == self::TYPE_NEGATIVE;
    }

    /**
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->getBuyerFeedbackType() == self::TYPE_POSITIVE;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrder()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->getSelect()
                   ->join(
                       ['oi' => $this->activeRecordFactory->getObject('Order\Item')->getResource()->getMainTable()],
                       '`oi`.`order_id` = `main_table`.`id`',
                       []
                   )
                   ->join(
                       [
                           'eoi' => $this->activeRecordFactory->getObject('Ebay_Order_Item')
                                                              ->getResource()
                                                              ->getMainTable(),
                       ],
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

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setAccountId(int $value): self
    {
        $this->setData('account_id', $value);

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return (int)$this->getData('account_id');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setEbayItemId(string $value): self
    {
        $this->setData('ebay_item_id', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getEbayItemId(): string
    {
        return (string)$this->getData('ebay_item_id');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setEbayItemTitle(string $value): self
    {
        $this->setData('ebay_item_title', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getEbayItemTitle(): string
    {
        return (string)$this->getData('ebay_item_title');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setEbayTransactionId(string $value): self
    {
        $this->setData('ebay_transaction_id', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getEbayTransactionId(): string
    {
        return (string)$this->getData('ebay_transaction_id');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setBuyerName(string $value): self
    {
        $this->setData('buyer_name', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerName(): string
    {
        return (string)$this->getData('buyer_name');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setBuyerFeedbackId(string $value): self
    {
        $this->setData('buyer_feedback_id', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerFeedbackId(): string
    {
        return (string)$this->getData('buyer_feedback_id');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setBuyerFeedbackText(string $value): self
    {
        $this->setData('buyer_feedback_text', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerFeedbackText(): string
    {
        return (string)$this->getData('buyer_feedback_text');
    }

    /**
     * @param \DateTime $value
     *
     * @return $this
     */
    public function setBuyerFeedbackDate(\DateTime $value): self
    {
        $this->setData('buyer_feedback_date', $value->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getBuyerFeedbackDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData('buyer_feedback_date'));
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setBuyerFeedbackType(string $value): self
    {
        $this->setData('buyer_feedback_type', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerFeedbackType(): string
    {
        return (string)$this->getData('buyer_feedback_type');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setSellerFeedbackId(string $value): self
    {
        $this->setData('seller_feedback_id', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerFeedbackId(): string
    {
        return (string)$this->getData('seller_feedback_id');
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setSellerFeedbackText(string $value): self
    {
        $this->setData('seller_feedback_text', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerFeedbackText(): string
    {
        return (string)$this->getData('seller_feedback_text');
    }

    /**
     * @param \DateTime $value
     *
     * @return $this
     */
    public function setSellerFeedbackDate(\DateTime $value): self
    {
        $this->setData('seller_feedback_date', $value->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getSellerFeedbackDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData('seller_feedback_date'));
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setSellerFeedbackType(string $value): self
    {
        $this->setData('seller_feedback_type', $value);

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerFeedbackType(): string
    {
        return (string)$this->getData('seller_feedback_type');
    }

    /**
     * @param \DateTime $value
     *
     * @return $this
     */
    public function setLastResponseAttemptDate(\DateTime $value): self
    {
        $this->setData('last_response_attempt_date', $value->format('Y-m-d H:i:s'));

        return $this;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getLastResponseAttemptDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData('last_response_attempt_date'));
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsCriticalErrorReceived(bool $value): self
    {
        $this->setData('is_critical_error_receive', (int)$value);

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCriticalErrorReceived(): bool
    {
        return (bool)$this->getData('is_critical_error_receive');
    }
}
