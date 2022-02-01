<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Registration;

/**
 * Class \Ess\M2ePro\Model\Registration\Manager
 */
class Manager
{
    private $register;
    private $infoFactory;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $register,
        \Ess\M2ePro\Model\Registration\InfoFactory $infoFactory
    ) {
        $this->register    = $register;
        $this->infoFactory = $infoFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Registration\Info
     */
    public function getInfo()
    {
        $data = $this->register->getValueFromJson('/registration/user_info/');

        return $this->infoFactory->create([
            'email'       => isset($data['email']) ? $data['email'] : null,
            'firstname'   => isset($data['firstname']) ? $data['firstname'] : null,
            'lastname'    => isset($data['lastname']) ? $data['lastname'] : null,
            'phone'       => isset($data['phone']) ? $data['phone'] : null,
            'country'     => isset($data['country']) ? $data['country'] : null,
            'city'        => isset($data['city']) ? $data['city'] : null,
            'postal_code' => isset($data['postal_code']) ? $data['postal_code'] : null
        ]);
    }

    /**
     * @param \Ess\M2ePro\Model\Registration\Info $info
     *
     * @return void
     */
    public function saveInfo(\Ess\M2ePro\Model\Registration\Info $info)
    {
        $data = [
            'email'       => $info->getEmail(),
            'firstname'   => $info->getFirstname(),
            'lastname'    => $info->getLastname(),
            'phone'       => $info->getPhone(),
            'country'     => $info->getCountry(),
            'city'        => $info->getCity(),
            'postal_code' => $info->getPostalCode(),
        ];

        $this->register->setValue('/registration/user_info/', $data);
    }

    /**
     * @return bool
     */
    public function isExistInfo()
    {
        return !empty($this->register->getValueFromJson('/registration/user_info/'));
    }
}
