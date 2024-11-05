<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\CategoryModifier;

class CategoryDiffStub extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isDifferent(): bool
    {
        return true;
    }

    public function isCategoriesDifferent(): bool // Model/Ebay/Template/Category/ChangeProcessor.php
    {
        return true;
    }
}
