<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class AfterGetTokenAutomatic extends AfterGetTokenAbstract
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);

        $this->sessionHelper = $sessionHelper;
    }

    protected function getAccountData()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->indexAction();
        }

        $requiredFields = [
            'Merchant',
            'Marketplace',
            'MWSAuthToken',
            'Signature',
            'SignedString'
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                $message = $this->__('The Amazon token obtaining is currently unavailable.');
                throw new \Exception($message);
            }
        }

        return array_merge(
            $this->getAmazonAccountDefaultSettings(),
            [
                'title'          => $params['Merchant'],
                'marketplace_id' => $this->sessionHelper->getValue('marketplace_id'),
                'merchant_id'    => $params['Merchant'],
                'token'          => $params['MWSAuthToken'],
            ]
        );
    }
}
