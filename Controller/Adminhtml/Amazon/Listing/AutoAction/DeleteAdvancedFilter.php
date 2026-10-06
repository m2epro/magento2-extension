<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class DeleteAdvancedFilter extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAdvancedFilterRepository;
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Delete $amazonAdvancedFilterDelete;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAdvancedFilterRepository,
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Delete $amazonAdvancedFilterDelete,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonAdvancedFilterRepository = $amazonAdvancedFilterRepository;
        $this->amazonAdvancedFilterDelete = $amazonAdvancedFilterDelete;
    }

    public function execute()
    {
        $advancedFilterId = (int)$this->getRequest()->getParam('advanced_filter_id');
        $advancedFilter = $this->amazonAdvancedFilterRepository->get($advancedFilterId);
        $this->amazonAdvancedFilterDelete->execute($advancedFilter);
    }
}
