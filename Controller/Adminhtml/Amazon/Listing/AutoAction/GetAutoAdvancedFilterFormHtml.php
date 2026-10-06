<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class GetAutoAdvancedFilterFormHtml extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $autoAdvancedFilterRepository;
    private \Ess\M2ePro\Model\Amazon\Listing\Repository $amazonListingRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $autoAdvancedFilterRepository,
        \Ess\M2ePro\Model\Amazon\Listing\Repository $amazonListingRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->autoAdvancedFilterRepository = $autoAdvancedFilterRepository;
        $this->amazonListingRepository = $amazonListingRepository;
    }

    public function execute()
    {
        $block = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\AdvancedFilter\Form::class,
                '',
                [
                    'advancedFilter' => $this->getAdvancedFilterFromRequest(),
                    'listing' => $this->getListingFromRequest(),
                ]
            );

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    private function getAdvancedFilterFromRequest(): ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter
    {
        $advancedFilterId = (int)$this->getRequest()->getParam('advanced_filter_id');

        return $this->autoAdvancedFilterRepository->find($advancedFilterId);
    }

    private function getListingFromRequest(): \Ess\M2ePro\Model\Listing
    {
        $listingId = (int)$this->getRequest()->getParam('listing_id');

        return $this->amazonListingRepository->get($listingId);
    }
}
