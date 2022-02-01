<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\Registration;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\Registration\CreateLicense
 */
class CreateLicense extends \Ess\M2ePro\Controller\Adminhtml\Wizard\Registration
{
    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    /** @var \Ess\M2ePro\Model\Registration\Info\Factory */
    private $infoFactory;

    public function __construct(
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\Registration\InfoFactory $infoFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        $this->manager = $manager;
        $this->infoFactory = $infoFactory;
        parent::__construct($nameBuilder, $context);
    }

    public function execute()
    {
        if ($this->getHelper('Server_Maintenance')->isNow()) {
            $message = 'The action is temporarily unavailable. M2E Pro Server is under maintenance.';
            $message .= ' Please try again later.';

            $this->setJsonContent([
                'status'  => false,
                'message' => $message,
            ]);

            return $this->getResult();
        }

        $requiredKeys = [
            'email',
            'firstname',
            'lastname',
            'phone',
            'country',
            'city',
            'postal_code',
        ];

        $licenseData = [];
        foreach ($requiredKeys as $key) {
            if ($tempValue = $this->getRequest()->getParam($key)) {
                $licenseData[$key] = $this->getHelper('Data')->escapeJs(
                    $this->getHelper('Data')->escapeHtml($tempValue)
                );
                continue;
            }

            $response = [
                'status'  => false,
                'message' => $this->__('You should fill all required fields.'),
            ];
            $this->setJsonContent($response);

            return $this->getResult();
        }

        $info = $this->infoFactory->create([
            'email'       => $licenseData['email'],
            'firstname'   => $licenseData['firstname'],
            'lastname'    => $licenseData['lastname'],
            'phone'       => $licenseData['phone'],
            'country'     => $licenseData['country'],
            'city'        => $licenseData['city'],
            'postal_code' => $licenseData['postal_code']
        ]);

        $this->manager->saveInfo($info);

        if ($this->getHelper('Module\License')->getKey()) {
            $this->setJsonContent(['status' => true]);

            return $this->getResult();
        }

        $message = null;

        try {
            $licenseResult = $this->getHelper('Module\License')->obtainRecord($info);
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $licenseResult = false;
            $message       = $this->__($e->getMessage());
        }

        if (!$licenseResult) {
            if (!$message) {
                $message = $this->__('License Creation is failed. Please contact M2E Pro Support for resolution.');
            }

            $this->setJsonContent([
                'status'  => $licenseResult,
                'message' => $message,
            ]);

            return $this->getResult();
        }

        try {
            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
            );
        } catch (\Exception $e) {}

        $this->setJsonContent(['status' => $licenseResult]);

        return $this->getResult();
    }
}
