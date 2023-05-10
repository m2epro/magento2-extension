<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments;

interface UrlStorageInterface
{
    public function getUrlForLateShipments(): string;

    public function getUrlForOver2Days(): string;

    public function getUrlForToday(): string;

    public function getUrlForTotal(): string;
}
