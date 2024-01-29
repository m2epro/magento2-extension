<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule;

class ViewState
{
    private const PREFIX_ROOT = 'rule_view_state_';
    private const PREFIX_CREATION = self::PREFIX_ROOT . 'creation_';
    private const PREFIX_UPDATING =  self::PREFIX_ROOT . 'updating_';
    private const PREFIX_SELECT = self::PREFIX_ROOT . 'select_';
    private const PREFIX_UNSELECT = self::PREFIX_ROOT . 'unselect_';

    /** @var string */
    private $viewKey;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $session;
    /** @var bool */
    private $isShowRuleBlock = false;

    public function __construct(string $viewKey, \Ess\M2ePro\Helper\Data\Session $session)
    {
        $this->viewKey = $viewKey;
        $this->session = $session;
    }

    //  ---------------------------------------------

    public function isWithoutState(): bool
    {
        return !$this->isStateCreation()
            && !$this->isStateUpdating()
            && !$this->isStateSelected()
            && !$this->isStateUnselected();
    }

    //  ---------------------------------------------

    public function isStateCreation(): bool
    {
        return $this->session->getValue(self::PREFIX_CREATION . $this->viewKey) === 'true';
    }

    public function setStateCreation(): void
    {
        $this->reset();
        $this->session->setValue(self::PREFIX_CREATION . $this->viewKey, 'true');
    }

    //  ---------------------------------------------

    public function isStateUpdating(): bool
    {
        return $this->session->getValue(self::PREFIX_UPDATING . $this->viewKey) !== null;
    }

    public function getUpdatedEntityId(): int
    {
        $entityId = $this->session->getValue(self::PREFIX_UPDATING . $this->viewKey);

        if ($entityId === null) {
            throw new \LogicException('Updated rule entity id not found');
        }

        return $entityId;
    }

    public function setStateUpdating(int $updatedRuleEntityId): void
    {
        $this->reset();
        $this->session->setValue(self::PREFIX_UPDATING . $this->viewKey, $updatedRuleEntityId);
    }

    //  ---------------------------------------------

    public function isStateSelected(): bool
    {
        return !empty($this->session->getValue(self::PREFIX_SELECT . $this->viewKey));
    }

    public function getSelectedEntityId(): int
    {
        $entityId = $this->session->getValue(
            self::PREFIX_SELECT . $this->viewKey
        )['selected_rule_entity_id'] ?? null;

        if ($entityId === null) {
            throw new \LogicException('Selected rule entity id not found');
        }

        return (int)$entityId;
    }

    public function getIsEntityRecentlyCreated($reset = false): bool
    {
        $value = $this->session->getValue(self::PREFIX_SELECT . $this->viewKey);
        if (empty($value)) {
            return false;
        }

        $flag = $value['entity_recently_created'] ?? false;
        if ($reset) {
            unset($value['entity_recently_created']);
            $this->session->setValue(self::PREFIX_SELECT . $this->viewKey, $value);
        }

        return $flag;
    }

    public function setStateSelect(int $selectedRuleEntityId, bool $entityRecentlyCreated = false): void
    {
        $this->reset();
        $this->session->setValue(
            self::PREFIX_SELECT . $this->viewKey,
            [
                'selected_rule_entity_id' => $selectedRuleEntityId,
                'entity_recently_created' => $entityRecentlyCreated,
            ]
        );
    }

    //  ---------------------------------------------

    public function isStateUnselected(): bool
    {
        return $this->session->getValue(self::PREFIX_UNSELECT . $this->viewKey) === 'true';
    }

    public function setStateUnselect(): void
    {
        $this->reset();
        $this->session->setValue(self::PREFIX_UNSELECT . $this->viewKey, 'true');
    }

    //  ---------------------------------------------

    public function isShowRuleBlock(): bool
    {
        return $this->isShowRuleBlock;
    }

    public function setIsShowRuleBlock(bool $isShowRuleBlock): void
    {
        $this->isShowRuleBlock = $isShowRuleBlock;
    }

    //  ---------------------------------------------

    private function reset(): void
    {
        $this->session->removeValue(self::PREFIX_CREATION . $this->viewKey);
        $this->session->removeValue(self::PREFIX_UPDATING . $this->viewKey);
        $this->session->removeValue(self::PREFIX_SELECT . $this->viewKey);
        $this->session->removeValue(self::PREFIX_UNSELECT . $this->viewKey);
    }

    public function getViewKey(): string
    {
        return $this->viewKey;
    }
}
