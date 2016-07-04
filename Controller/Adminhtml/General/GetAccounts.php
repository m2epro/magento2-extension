<?php
/**
 * Created by PhpStorm.
 * User: HardRock
 * Date: 14.03.2016
 * Time: 16:40
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

            // TODO join marketplace table
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