<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class GetAccounts extends General
{
    //########################################

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');

        $collection = $this->parentFactory->getObject($component,'Account')->getCollection();

        $accounts = array();
        foreach ($collection->getItems() as $account) {
            $data = array(
                'id' => $account->getId(),
                'title' => $this->getHelper('Data')->escapeHtml($account->getTitle())
            );

            if ($component == \Ess\M2ePro\Helper\Component\Amazon::NICK) {
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

    //########################################
}