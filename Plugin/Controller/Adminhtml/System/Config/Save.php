<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Controller\Adminhtml\System\Config;

class Save
{
    protected $request;
    protected $moduleHelper;

    //########################################

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Ess\M2ePro\Helper\Module $moduleHelper
    )
    {
        $this->request = $request;
        $this->moduleHelper = $moduleHelper;
    }

    //########################################

    public function beforeExecute($subject)
    {
        $groups = $this->request->getPostValue('groups');

        if (isset($groups['ebay_mode']['fields']['ebay_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/component/ebay/', 'mode',
                (int)$groups['ebay_mode']['fields']['ebay_mode_field']['value']
            );
        }

        if (isset($groups['amazon_mode']['fields']['amazon_mode_field']['value'])) {
            $this->moduleHelper->getConfig()->setGroupValue(
                '/component/amazon/', 'mode',
                (int)$groups['ebay_mode']['fields']['ebay_mode_field']['value']
            );
        }
    }

    //########################################
}