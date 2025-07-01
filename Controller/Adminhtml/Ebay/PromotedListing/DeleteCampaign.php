<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class DeleteCampaign extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteCampaign $deleteCampaign,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->deleteCampaign = $deleteCampaign;
        $this->campaignRepository = $campaignRepository;
    }

    public function execute()
    {
        try {
            $this->deleteCampaign->execute($this->getCampaignFromRequest(), true);
            $this->setJsonContent(['result' => true]);
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
                'fail_messages' => [$exception->getMessage()]
            ]);
        }

        return $this->getResult();
    }

    private function getCampaignFromRequest(): \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign
    {
        return $this->campaignRepository
            ->get((int)$this->getRequest()->getParam('campaign_id'));
    }
}
