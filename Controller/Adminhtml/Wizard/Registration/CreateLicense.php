<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\Registration;


class CreateLicense extends \Ess\M2ePro\Controller\Adminhtml\Wizard\Registration
{
    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenanceHelper;

    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    /** @var \Ess\M2ePro\Model\Registration\InfoFactory */
    private $infoFactory;

    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenanceHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\Registration\InfoFactory $infoFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);

        $this->manager = $manager;
        $this->infoFactory = $infoFactory;
        $this->licenseHelper = $licenseHelper;
        $this->dataHelper = $dataHelper;
        $this->serverMaintenanceHelper = $serverMaintenanceHelper;
    }

    public function execute()
    {
        if ($this->serverMaintenanceHelper->isNow()) {
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
                $licenseData[$key] = $this->dataHelper->escapeJs(
                    $this->dataHelper->escapeHtml($tempValue)
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
            'postal_code' => $licenseData['postal_code'],
        ]);

        $this->manager->saveInfo($info);

        if ($this->licenseHelper->getKey()) {
            $this->setJsonContent(['status' => true]);

            return $this->getResult();
        }

        $message = null;

        try {
            $licenseResult = $this->licenseHelper->obtainRecord($info);
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $licenseResult = false;
            $message = $this->__($e->getMessage());
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
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );
        } catch (\Exception $e) {
        }

        $this->setJsonContent(['status' => $licenseResult]);

        return $this->getResult();
    }
}
