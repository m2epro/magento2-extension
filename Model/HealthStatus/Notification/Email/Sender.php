<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification\Email;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Notification\Email\Sender
 */
class Sender extends \Ess\M2ePro\Model\AbstractModel
{
    private const FROM_NAME = 'M2E Pro Health Status';
    private const TEMPLATE_PATH = 'm2epro_health_status_notification_email_template';

    /** @var \Magento\Framework\Translate\Inline\StateInterface */
    private $inlineTranslation;
    /** @var \Magento\Framework\Mail\Template\TransportBuilder */
    private $transportBuilder;
    /** @var \Magento\User\Model\ResourceModel\User\CollectionFactory */
    private $userCollectionFactory;
    /** @var \Ess\M2ePro\Model\HealthStatus\Notification\Settings */
    private $healthStatusSettings;
    /** @var \Ess\M2ePro\Model\HealthStatus\Notification\MessageBuilder */
    private $healthStatusMessageBuilder;

    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        \Ess\M2ePro\Model\HealthStatus\Notification\Settings $healthStatusSettings,
        \Ess\M2ePro\Model\HealthStatus\Notification\MessageBuilder $healthStatusMessageBuilder,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->healthStatusSettings = $healthStatusSettings;
        $this->healthStatusMessageBuilder = $healthStatusMessageBuilder;
    }

    public function send()
    {
        $this->inlineTranslation->suspend();
        $transport = $this->transportBuilder
            ->setTemplateIdentifier(self::TEMPLATE_PATH)
            ->setTemplateOptions(
                [
                    'area' => 'adminhtml',
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
            ->setTemplateVars([
                'header' => $this->healthStatusMessageBuilder->getHeader(),
                'message' => $this->healthStatusMessageBuilder->getMessage(),

            ])
            ->setFrom([
                'name' => self::FROM_NAME,
                'email' => $this->getAdminUserEmail(),
            ])
            ->addTo($this->healthStatusSettings->getEmail(), 'Magento Administrator')
            ->getTransport();

        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    private function getAdminUserEmail(): string
    {
        $collection = $this->userCollectionFactory->create();
        $collection->setOrder('user_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $collection->setPageSize(1);
        $collection->addFieldToFilter('is_active', 1);

        return $collection->getFirstItem()->getData('email');
    }
}
