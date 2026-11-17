<?php
declare(strict_types=1);
namespace SdutSwap\Services;

use SdutSwap\Repositories\BlockRepository;
use SdutSwap\Repositories\UserRepository;

/** Rules around blocking: who can be blocked, and whether two users may message each other. */
final class BlockService
{
    public const OK = 'ok';
    public const SELF = 'self';
    public const NOT_FOUND = 'not_found';
    public const ADMIN = 'admin';

    public function __construct(private BlockRepository $blocks, private UserRepository $users)
    {
    }

    /** Returns one of the class constants. Staff accounts cannot be blocked so users can always reach support. */
    public function block(int $blockerId, int $targetId): string
    {
        if ($blockerId === $targetId) return self::SELF;
        $target = $this->users->findById($targetId);
        if ($target === null) return self::NOT_FOUND;
        if (($target['role'] ?? '') === 'admin') return self::ADMIN;
        $this->blocks->block($blockerId, $targetId);
        return self::OK;
    }

    public function unblock(int $blockerId, int $targetId): bool
    {
        return $this->blocks->unblock($blockerId, $targetId);
    }

    /** Messaging is allowed only when neither user has blocked the other. */
    public function canMessage(int $a, int $b): bool
    {
        return !$this->blocks->isBlockedEitherWay($a, $b);
    }
}
