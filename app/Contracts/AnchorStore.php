<?php

namespace App\Contracts;

/**
 * Where credential hashes get anchored. Two drivers: an EVM contract (Hardhat)
 * and the local hash-chain table used as an offline fallback.
 */
interface AnchorStore
{
    public function driver(): string;

    public function available(): bool;

    public function anchor(int $locationId, string $dataHash, ?string $actor): void;

    /** @return array{data_hash: string, actor: ?string, timestamp: int, index: int, tx: ?string}|null */
    public function latest(int $locationId): ?array;

    /** @return array<int, array{index: int, location_id: ?int, data_hash: string, actor: ?string, timestamp: int, extra: ?string}> */
    public function all(): array;

    /** @return array{valid: bool, blocks: int, driver: string, error?: string, broken_at?: int} */
    public function status(): array;
}
