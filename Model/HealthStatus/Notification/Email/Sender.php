<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification\Email;

class Sender extends \Ess\M2ePro\Model\AbstractModel
{
    const FROM_NAME  = 'M2E Pro Health Status';
    const FROM_EMAIL = 'do-not-reply';

    const TEMPLATE_PATH = 'm2epro_health_status_notification_email_template';

    /** @var \Magento\Framework\Translate\Inline\StateInterface */
    protected $inlineTranslation;

    /** @var \Magento\Framework\Mail\Template\TransportBuilder */
    protected $transportBuilder;

    //########################################

    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ){
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder  = $transportBuilder;
    }

    //########################################

    public function send()
    {
        $settings = $this->modelFactory->getObject('HealthStatus\Notification\Settings');
        $messageBuilder = $this->modelFactory->getObject('HealthStatus\Notification\MessageBuilder');

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
                'header'  => $messageBuilder->getHeader(),
                'message' => $messageBuilder->getMessage(),
            ])
            ->setFrom([
                'name'  => self::FROM_NAME,
                'email' => self::FROM_EMAIL .'@'. $this->getHelper('Client')->getDomain()
            ])
            ->addTo($settings->getEmail(), 'Magento Administrator')
            ->getTransport();

        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    //########################################
}