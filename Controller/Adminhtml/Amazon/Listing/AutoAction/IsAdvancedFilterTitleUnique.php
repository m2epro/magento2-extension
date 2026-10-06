<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class IsAdvancedFilterTitleUnique extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    private \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAdvancedFilterRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter\Repository $amazonAdvancedFilterRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonAdvancedFilterRepository = $amazonAdvancedFilterRepository;
    }

    public function execute()
    {
        $advancedFilterId = $this->getRequest()->getParam('advanced_filter_id');
        if (empty($advancedFilterId)) {
            $advancedFilterId = null;
        }

        $title = $this->getRequest()->getParam('title');
        if ($title == '') {
            $this->setJsonContent(['unique' => false]);

            return $this->getResult();
        }

        $result = $this->amazonAdvancedFilterRepository->isUniqueTitle(
            $title,
            (int)$this->getRequest()->getParam('listing_id'),
            $this->getAdvancedFilterIdFromRequest()
        );

        $this->setJsonContent(['unique' => $result]);

        return $this->getResult();
    }

    private function getAdvancedFilterIdFromRequest(): ?int
    {
        $value = $this->getRequest()->getParam('advanced_filter_id');
        if (empty($value)) {
            return null;
        }

        return (int)$value;
    }
}
