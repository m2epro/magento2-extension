<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data;

class Session
{
    /** @var \Magento\Framework\Session\SessionManager */
    private $session;

    /**
     * @param \Magento\Framework\Session\SessionManager $session
     */
    public function __construct(
        \Magento\Framework\Session\SessionManager $session
    ) {
        $this->session = $session;
    }

    // ----------------------------------------

    /**
     * @param string $key
     * @param bool $clear
     *
     * @return mixed
     */
    public function getValue($key, $clear = false)
    {
        return $this->session->getData(
            \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key,
            $clear
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setValue($key, $value): void
    {
        $this->session->setData(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key, $value);
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getAllValues(): array
    {
        $return = [];
        $session = $this->session->getData();

        foreach ($session as $key => $value) {
            if (strpos($key, \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER) === 0) {
                $tempReturnedKey = substr($key, strlen(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER)+1);
                $return[$tempReturnedKey] = $this->session->getData($key);
            }
        }

        return $return;
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function removeValue($key): void
    {
        $this->session->getData(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key, true);
    }

    /**
     * @return void
     */
    public function removeAllValues(): void
    {
        $session = $this->session->getData();

        foreach ($session as $key => $value) {
            if (strpos($key, \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER) === 0) {
                $this->session->getData($key, true);
            }
        }
    }
}
