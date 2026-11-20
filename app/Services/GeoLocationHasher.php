<?php

namespace App\Services;

use App\Models\GeoLocation;

class GeoLocationHasher
{
    /**
     * Covers every column of the row except `data_hash` itself and `updated_at`,
     * so any field added later is protected automatically. `updated_at` is
     * excluded because Laravel rewrites it on every save — including the save
     * that stores this hash — so covering it would invalidate itself.
     *
     * Decimals and timestamps are normalized because SQLite returns them as
     * strings while a fresh model holds PHP objects.
     */
    public function hash(GeoLocation $location): string
    {
        $data = $location->getAttributes();

        unset($data['data_hash'], $data['updated_at']);

        // ponytail: only the decimal columns need formatting; add here if more appear.
        $data['latitude'] = number_format((float) $data['latitude'], 7, '.', '');
        $data['longitude'] = number_format((float) $data['longitude'], 7, '.', '');

        foreach ($data as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        ksort($data);

        return hash(
            'sha256',
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
