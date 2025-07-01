<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing;

use Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign as CampaignResource;

class Campaign extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /**
     * @see https://developer.ebay.com/api-docs/sell/marketing/types/pls:FundingModelEnum
     */
    public const TYPE_COST_PER_SALE = 'COST_PER_SALE';
    public const TYPE_COST_PER_CLICK = 'COST_PER_CLICK';

    /**
     * @see https://developer.ebay.com/api-docs/sell/marketing/types/pls:CampaignStatusEnum
     */
    public const STATUS_DELETED = 'DELETED';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ENDED = 'ENDED';
    public const STATUS_ENDING_SOON = 'ENDING_SOON';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_SCHEDULED = 'SCHEDULED';
    public const STATUS_SYSTEM_PAUSED = 'SYSTEM_PAUSED';

    private \Ess\M2ePro\Model\Ebay\Account $account;

    private \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository,
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
        $this->ebayAccountRepository = $ebayAccountRepository;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(CampaignResource::class);
    }

    public function initFromChannelCampaign(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        Channel\Dto\Campaign $receivedCampaign
    ): self {
        $this
            ->setData(CampaignResource::COLUMN_ACCOUNT_ID, $ebayAccount->getId())
            ->setData(CampaignResource::COLUMN_MARKETPLACE_ID, $receivedCampaign->getMarketplaceId())
            ->setData(CampaignResource::COLUMN_EBAY_CAMPAIGN_ID, $receivedCampaign->getId())
            ->setData(CampaignResource::COLUMN_NAME, $receivedCampaign->getName())
            ->setData(CampaignResource::COLUMN_STATUS, $receivedCampaign->getStatus())
            ->setData(CampaignResource::COLUMN_TYPE, $receivedCampaign->getType())
            ->setData(CampaignResource::COLUMN_START_DATE, $receivedCampaign->getFormattedStartDate())
            ->setData(CampaignResource::COLUMN_END_DATE, $receivedCampaign->getFormattedEndDate())
            ->setData(CampaignResource::COLUMN_RATE, $receivedCampaign->getRate());

        return $this;
    }

    public function updateFromChannelCampaign(Channel\Dto\Campaign $receivedCampaign)
    {
        $this
            ->setData(CampaignResource::COLUMN_MARKETPLACE_ID, $receivedCampaign->getMarketplaceId())
            ->setData(CampaignResource::COLUMN_EBAY_CAMPAIGN_ID, $receivedCampaign->getId())
            ->setData(CampaignResource::COLUMN_NAME, $receivedCampaign->getName())
            ->setData(CampaignResource::COLUMN_STATUS, $receivedCampaign->getStatus())
            ->setData(CampaignResource::COLUMN_TYPE, $receivedCampaign->getType())
            ->setData(CampaignResource::COLUMN_START_DATE, $receivedCampaign->getFormattedStartDate())
            ->setData(CampaignResource::COLUMN_END_DATE, $receivedCampaign->getFormattedEndDate())
            ->setData(CampaignResource::COLUMN_RATE, $receivedCampaign->getRate());
    }

    /**
     * @return bool return `true` if any field updated.
     */
    public function updateNameStartDateAndEndDate(string $name, \DateTime $startDate, ?\DateTime $endDate): bool
    {
        $this->setData(CampaignResource::COLUMN_NAME, $name);
        $this->setData(CampaignResource::COLUMN_START_DATE, $startDate->format('Y-m-d H:i:s'));
        if ($endDate === null) {
            $this->setData(CampaignResource::COLUMN_END_DATE, null);
        } else {
            $this->setData(CampaignResource::COLUMN_END_DATE, $endDate->format('Y-m-d H:i:s'));
        }

        return $this->dataHasChangedFor(CampaignResource::COLUMN_NAME)
            || $this->dataHasChangedFor(CampaignResource::COLUMN_START_DATE)
            || $this->dataHasChangedFor(CampaignResource::COLUMN_END_DATE);
    }

    public function getEbayAccount(): \Ess\M2ePro\Model\Ebay\Account
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->account)) {
            $this->account = $this->ebayAccountRepository->getByAccountId($this->getAccountId());
        }

        return $this->account;
    }

    public function getId(): int
    {
        return (int)$this->getData(CampaignResource::COLUMN_ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(CampaignResource::COLUMN_ACCOUNT_ID);
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getData(CampaignResource::COLUMN_MARKETPLACE_ID);
    }

    public function getEbayCampaignId(): string
    {
        return (string)$this->getData(CampaignResource::COLUMN_EBAY_CAMPAIGN_ID);
    }

    public function getName(): string
    {
        return (string)$this->getData(CampaignResource::COLUMN_NAME);
    }

    public function getStartDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt($this->getData(CampaignResource::COLUMN_START_DATE));
    }

    public function getEndDate(): ?\DateTime
    {
        $endDate = $this->getData(CampaignResource::COLUMN_END_DATE);
        if (empty($endDate)) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($endDate);
    }

    public function getRate(): float
    {
        return (float)$this->getData(CampaignResource::COLUMN_RATE);
    }
}
