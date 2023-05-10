<?php

namespace Ess\M2ePro\Model\Dashboard\Sales;

interface CalculatorInterface
{
    public function getAmountPointSetFor24Hours(): PointSet;

    public function getQtyPointSetFor24Hours(): PointSet;

    public function getAmountPointSetFor7Days(): PointSet;

    public function getQtyPointSetFor7Days(): PointSet;

    public function getAmountPointSetForToday(): PointSet;

    public function getQtyPointSetForToday(): PointSet;
}
