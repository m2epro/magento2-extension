<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class GetAdvancedFilterGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Listing\Repository $listingRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Repository $listingRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->listingRepository = $listingRepository;
    }

    public function execute()
    {
        $grid = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\AdvancedFilter\Grid::class,
                '',
                [
                    'listing' => $this->listingRepository->get((int)$this->getRequest()->getParam('listing_id')),
                ]
            );
        $this->setAjaxContent($grid);

        return $this->getResult();
    }
}
