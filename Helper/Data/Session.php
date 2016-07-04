<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data;

class Session extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $session;

    //########################################

    public function __construct(
        \Magento\Framework\Session\SessionManager $session,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->session = $session;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getValue($key, $clear = false)
    {
        return $this->session->getData(
            \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key, $clear
        );
    }

    public function setValue($key, $value)
    {
        $this->session->setData(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key, $value);
    }

    // ---------------------------------------

    public function getAllValues()
    {
        $return = array();
        $session = $this->session->getData();

        $identifierLength = strlen(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER);

        foreach ($session as $key => $value) {
            if (substr($key, 0, $identifierLength) == \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER) {
                $tempReturnedKey = substr($key, strlen(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER)+1);
                $return[$tempReturnedKey] = $this->session->getData($key);
            }
        }

        return $return;
    }

    //########################################

    public function removeValue($key)
    {
        $this->session->getData(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key, true);
    }

    public function removeAllValues()
    {
        $session = $this->session->getData();
        $identifierLength = strlen(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER);

        foreach ($session as $key => $value) {
            if (substr($key, 0, $identifierLength) == \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER) {
                $this->session->getData($key, true);
            }
        }
    }

    //########################################
}