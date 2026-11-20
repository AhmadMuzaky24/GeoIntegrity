<?php

namespace App\Services;

use App\Models\Block;
use Illuminate\Support\Facades\DB;

/**
 * Append-only proof-of-work chain that anchors geo location data hashes.
 *
 * ponytail: PoW difficulty fixed at 3 hex zeros — enough to make tampering
 * detectable and cheap to demo. Move to config when mining cost matters.
 */
class BlockchainLedger
{
    private const DIFFICULTY = 3;

    public function append(string $dataHash, ?int $locationId = null, ?string $actor = null): Block
    {
        return DB::transaction(function () use ($dataHash, $locationId, $actor) {
            $prev = Block::orderByDesc('height')->lockForUpdate()->first();

            $block = new Block([
                'height' => $prev ? $prev->height + 1 : 0,
                'geo_location_id' => $locationId,
                'actor' => $actor,
                'data_hash' => $dataHash,
                'prev_hash' => $prev?->hash ?? str_repeat('0', 64),
                'timestamp' => now()->getTimestamp(),
            ]);

            $this->mine($block);
            $block->save();

            return $block;
        });
    }

    /** @return array{valid: bool, blocks: int, broken_at?: int} */
    public function verify(): array
    {
        $prevHash = str_repeat('0', 64);
        $expectedHeight = 0;

        foreach (Block::orderBy('height')->cursor() as $block) {
            if ((int) $block->height !== $expectedHeight
                || $block->prev_hash !== $prevHash
                || $block->hash !== $this->hash($block)) {
                return ['valid' => false, 'blocks' => $expectedHeight, 'broken_at' => (int) $block->height];
            }

            $prevHash = $block->hash;
            $expectedHeight++;
        }

        return ['valid' => true, 'blocks' => $expectedHeight];
    }

    public function hash(Block $block): string
    {
        return hash('sha256', implode('|', [
            (int) $block->height,
            $block->prev_hash,
            $block->data_hash,
            (int) $block->nonce,
            (int) $block->timestamp,
        ]));
    }

    private function mine(Block $block): void
    {
        $prefix = str_repeat('0', self::DIFFICULTY);

        for ($block->nonce = 0; ; $block->nonce++) {
            $block->hash = $this->hash($block);

            if (str_starts_with($block->hash, $prefix)) {
                return;
            }
        }
    }
}
