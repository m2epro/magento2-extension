<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Shipments;

interface UrlStorageInterface
{
    public function getUrlForLateShipments(): string;

    public function getUrlForShipByToday(): string;

    public function getUrlForShipByTomorrow(): string;

    public function getUrlForTwoAndMoreDays(): string;
}
