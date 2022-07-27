<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $componentEbayConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $componentEbayConfiguration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayConfiguration = $componentEbayConfiguration;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        try {
            $this->componentEbayConfiguration->setConfigValues($this->getRequest()->getParams());
            $this->setJsonContent(['success' => true]);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->setJsonContent(
                [
                    'success'  => false,
                    'messages' => [
                        ['error' => $this->__($e->getMessage())]
                    ]
                ]
            );
        }

        return $this->getResult();
    }
}
