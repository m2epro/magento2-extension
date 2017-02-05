<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification;

class MessageBuilder extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\UrlInterface */
    private $urlBuilder;

    //########################################

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ){
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->urlBuilder = $urlBuilder;
    }

    //########################################

    public function build()
    {
        return $this->getHeader() .': '. $this->getMessage();
    }

    //########################################

    public function getHeader()
    {
        return __('M2E Pro Health Status Notification');
    }

    public function getMessage()
    {
        $manageUrl = $this->urlBuilder->getUrl('m2epro/healthStatus/index');
        return __(<<<HTML
Something went wrong with your M2E Pro running and some actions from your side are required.
 A detailed information you can find in <a target="_blank" href="{$manageUrl}">M2E Pro Health Status Center</a>.
HTML
        );
    }

    //########################################
}