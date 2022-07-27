<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

class GetAccounts extends \Ess\M2ePro\Controller\Adminhtml\General
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        parent::__construct($context);

        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');

        $collection = $this->parentFactory->getObject($component, 'Account')->getCollection();

        $accounts = [];
        foreach ($collection->getItems() as $account) {
            $data = [
                'id' => $account->getId(),
                'title' => $this->dataHelper->escapeHtml($account->getTitle())
            ];

            if ($component == \Ess\M2ePro\Helper\Component\Amazon::NICK ||
                $component == \Ess\M2ePro\Helper\Component\Walmart::NICK) {
                $marketplace = $account->getChildObject()->getMarketplace();
                $data['marketplace_id'] = $marketplace->getId();
                $data['marketplace_title'] = $marketplace->getTitle();
                $data['marketplace_url'] = $marketplace->getUrl();
            }

            $accounts[] = $data;
        }

        $this->setJsonContent($accounts);
        return $this->getResult();
    }
}
