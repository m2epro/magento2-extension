<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class UpdateCampaign extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\UpdateCampaign $updateCampaign;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\UpdateCampaign $updateCampaign,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->updateCampaign = $updateCampaign;
        $this->campaignRepository = $campaignRepository;
    }

    public function execute()
    {
        try {
            $this->updateCampaign->execute(
                $this->getCampaignFromRequest(),
                $this->getNameFromRequest(),
                $this->getStartDateFromRequest(),
                $this->getEndDateFromRequest()
            );

            $this->setJsonContent([
                'result' => true,
                'message' => __('Campaign Updated')
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

    private function getCampaignFromRequest(): \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign
    {
        return $this->campaignRepository
            ->get((int)$this->getRequest()->getParam('id'));
    }

    private function getEbayCampaignIdFromRequest(): string
    {
        $ebayCampaignId = (string)$this->getRequest()->getParam('ebay_campaign_id');
        if (empty($ebayCampaignId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('eBay Campaign ID is a required.');
        }

        return $ebayCampaignId;
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
