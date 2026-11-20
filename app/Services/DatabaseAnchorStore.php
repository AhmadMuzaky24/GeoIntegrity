<?php

namespace App\Services;

use App\Contracts\AnchorStore;
use App\Models\Block;

/**
 * Offline driver: the append-only hash chain in the `blocks` table.
 */
class DatabaseAnchorStore implements AnchorStore
{
    public function __construct(private BlockchainLedger $ledger) {}

    public function driver(): string
    {
        return 'database';
    }

    public function available(): bool
    {
        return true;
    }

    public function anchor(int $locationId, string $dataHash, ?string $actor): void
    {
        $this->ledger->append($dataHash, $locationId, $actor);
    }

    public function latest(int $locationId): ?array
    {
        $block = Block::where('geo_location_id', $locationId)->latest('height')->first();

        if (! $block) {
            return null;
        }

        return [
            'data_hash' => $block->data_hash,
            'actor' => $block->actor,
            'timestamp' => (int) $block->timestamp,
            'index' => (int) $block->height,
            'tx' => $block->hash,
        ];
    }

    public function all(): array
    {
        return Block::with('location')->orderByDesc('height')->limit(200)->get()
            ->map(fn (Block $block) => [
                'index' => (int) $block->height,
                'location_id' => $block->geo_location_id,
                'data_hash' => $block->data_hash,
                'actor' => $block->actor,
                'timestamp' => (int) $block->timestamp,
                'extra' => $block->prev_hash,
            ])
            ->all();
    }

    public function status(): array
    {
        return $this->ledger->verify() + ['driver' => $this->driver()];
    }
}
