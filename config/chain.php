<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anchor driver
    |--------------------------------------------------------------------------
    | "evm"      → anchors are written to the GeoAnchor Solidity contract via
    |              JSON-RPC (Hardhat node by default). Needs `npx hardhat node`
    |              running and a deployed contract address.
    | "database" → local hash-chain in the `blocks` table (offline fallback).
    */
    'driver' => env('CHAIN_DRIVER', 'database'),

    'rpc_url' => env('CHAIN_RPC_URL', 'http://127.0.0.1:8545'),

    'contract' => env('CHAIN_CONTRACT_ADDRESS'),

    'node_bin' => env('CHAIN_NODE_BIN', 'node'),

    'write_script' => base_path('chain/scripts/anchor.js'),

];
