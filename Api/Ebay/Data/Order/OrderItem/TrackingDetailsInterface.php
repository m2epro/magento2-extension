<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order\OrderItem;

interface TrackingDetailsInterface
{
    public const NUMBER_KEY = 'number';
    public const TITLE_KEY = 'title';

    /**
     * @param string $number
     *
     * @return void
     */
    public function setNumber(string $number): void;

    /**
     * @return string|null
     */
    public function getNumber(): ?string;

    /**
     * @param string $title
     *
     * @return void
     */
    public function setTitle(string $title): void;

    /**
     * @return string|null
     */
    public function getTitle(): ?string;
}
