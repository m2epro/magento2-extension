<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $componentEbayConfiguration;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\ChangeIdentifierTrackerFactory */
    private $changeIdentifierTrackerFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Ess\M2ePro\Model\Ebay\Listing\Product\ChangeIdentifierTrackerFactory $changeIdentifierTrackerFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayConfiguration = $componentEbayConfiguration;
        $this->changeIdentifierTrackerFactory = $changeIdentifierTrackerFactory;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        $changeIdentifierTracker = $this->changeIdentifierTrackerFactory->create();

        $changeIdentifierTracker->startCheckChangeIdentifier();
        $this->componentEbayConfiguration->setConfigValues($this->getRequest()->getParams());
        $changeIdentifierTracker->tryCreateInstructionsForChange();

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
