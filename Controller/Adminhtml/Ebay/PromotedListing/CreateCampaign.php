<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class CreateCampaign extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository;
    private \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\CreateCampaign $createCampaign;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository,
        \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository,
        \Ess\M2ePro\Model\Ebay\PromotedListing\CreateCampaign $createCampaign,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->ebayAccountRepository = $ebayAccountRepository;
        $this->ebayMarketplaceRepository = $ebayMarketplaceRepository;
        $this->createCampaign = $createCampaign;
    }

    public function execute()
    {
        try {
            $this->createCampaign->execute(
                $this->getEbayAccountFromRequest(),
                $this->getMarketplaceFromRequest(),
                new \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\CreateCampaign(
                    $this->getNameFromRequest(),
                    $this->getStartDateFromRequest(),
                    $this->getEndDateFromRequest(),
                    $this->getRateFromRequest()
                )
            );

            $this->setJsonContent([
                'result' => true,
                'message' => __('Campaign Created')
            ]);
        } catch (\Ess\M2ePro\Model\Ebay\PromotedListing\CampaignException $exception) {
            $this->setJsonContent([
                'result' => false,
                'fail_messages' => array_map(function ($message) {
                    return $message->getText();
                }, $exception->getCampaignFailMessages()),
            ]);
        } catch (\Throwable $exception) {
            $this->setJsonContent([
                'result' => false,
                'fail_messages' => [$exception->getMessage()],
            ]);
        }

        return $this->getResult();
    }

    private function getEbayAccountFromRequest(): \Ess\M2ePro\Model\Ebay\Account
    {
        return $this->ebayAccountRepository
            ->getByAccountId((int)$this->getRequest()->getParam('account_id'));
    }

    private function getMarketplaceFromRequest(): \Ess\M2ePro\Model\Ebay\Marketplace
    {
        return $this->ebayMarketplaceRepository
            ->getByMarketplaceId((int)$this->getRequest()->getParam('marketplace_id'));
    }

    private function getNameFromRequest(): string
    {
        $name = (string)$this->getRequest()->getParam('name');
        if (empty($name)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Campaign Name is a required.');
        }

        return $name;
    }

    private function getStartDateFromRequest(): \DateTime
    {
        $startDate = $this->parseDateTime((string)$this->getRequest()->getParam('start_date'));
        if (empty($startDate)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Start Time is a required.');
        }

        return $startDate;
    }

    private function getEndDateFromRequest(): ?\DateTime
    {
        return $this->parseDateTime((string)$this->getRequest()->getParam('end_date'));
    }

    private function getRateFromRequest(): float
    {
        $rate = (float)$this->getRequest()->getParam('rate');
        if (empty($rate)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Promote Listings at rate is a required.');
        }

        return $rate;
    }

    private function parseDateTime(string $dateTimeString): ?\DateTime
    {
        if (empty($dateTimeString)) {
            return null;
        }

        $timestamp = \Ess\M2ePro\Helper\Date::parseDateFromLocalFormat($dateTimeString);

        $dateTime = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateTime->setTimestamp($timestamp);

        return \Ess\M2ePro\Helper\Date::createWithGmtTimeZone($dateTime);
    }
}
