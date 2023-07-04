<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order;

interface BuyerInterface
{
    public const NAME_KEY = 'name';
    public const EMAIL_KEY = 'email';
    public const USER_ID_KEY = 'user_id';
    public const MESSAGE_KEY = 'message';
    public const TAX_ID_KEY = 'tax_id';

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * @return string|null
     */
    public function getUserId(): ?string;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @return string|null
     */
    public function getTaxId(): ?string;
}
