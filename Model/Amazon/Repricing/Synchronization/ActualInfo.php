<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Synchronization;

class ActualInfo extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $amazonRepricingHelper;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $amazonRepricingHelper,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($activeRecordFactory, $amazonFactory, $resourceConnection, $helperFactory, $modelFactory);
        $this->amazonRepricingHelper = $amazonRepricingHelper;
        $this->helperException = $helperException;
    }

    public function run()
    {
        $response = $this->sendData();

        if ($response === false || empty($response['status'])) {
            return false;
        }

        if (!empty($response['email'])) {
            $this->getAmazonAccountRepricing()->setData('email', $response['email']);
        }

        $this->getAmazonAccountRepricing()->setData('total_products', $response['total_products_count']);
        $this->getAmazonAccountRepricing()->save();
    }

    private function sendData()
    {
        try {
            $result = $this->amazonRepricingHelper->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_ACCOUNT_GET,
                [
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                ]
            );

            $response = $result['response'];
            $this->processErrorMessages($response);

            return $response;
        } catch (\Exception $e) {
            $this->helperException->process($e);

            return false;
        }
    }
}
