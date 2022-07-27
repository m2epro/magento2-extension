<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

use Ess\M2ePro\Helper\Module;

class GetGlobalMessages extends Main
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    public function __construct(
        Module $moduleHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->moduleHelper = $moduleHelper;
    }

    public function execute()
    {
        if ($this->getCustomViewHelper()->isInstallationWizardFinished()) {
            $this->addLicenseNotifications();
        }

        $this->addNotifications($this->moduleHelper->getServerMessages());
        $this->addCronErrorMessage();
        $this->getCustomViewControllerHelper()->addMessages();

        $messages = $this->getMessageManager()->getMessages(
            true,
            \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
        )->getItems();

        foreach ($messages as &$message) {
            $message = [$message->getType() => $message->getText()];
        }

        $this->setJsonContent($messages);
        return $this->getResult();
    }
}
