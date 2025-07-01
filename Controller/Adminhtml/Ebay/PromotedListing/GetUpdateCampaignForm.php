<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class GetUpdateCampaignForm extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->campaignRepository = $campaignRepository;
    }

    public function execute()
    {
        $campaign = $this->campaignRepository->get((int)$this->getRequest()->getParam('campaign_id'));

        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\PromotedListing\Campaign\UpdateForm::class,
            '',
            [
                'campaign' => $campaign,
            ]
        );

        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }
}
