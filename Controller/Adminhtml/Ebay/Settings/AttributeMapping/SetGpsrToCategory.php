<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\AttributeMapping;

class SetGpsrToCategory extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService $gpsrService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->gpsrService = $gpsrService;
    }

    public function execute()
    {
        try {
            $this->gpsrService->setToCategories();

            $this->setJsonContent(['success' => true]);
        } catch (\Throwable $e) {
            $this->setJsonContent(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->getResult();
    }
}
