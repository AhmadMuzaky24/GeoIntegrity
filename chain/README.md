# GeoIntegrity chain (Solidity + Hardhat)

Anchors live in `contracts/GeoAnchor.sol`. Laravel talks to it over JSON-RPC:
reads with `eth_call`, writes through `scripts/anchor.js` (ethers signs the tx).

## First run

```bash
cd chain
npm install
npx hardhat test                      # 6 contract tests, no node needed
npx hardhat node                      # terminal 1: local chain on :8545
npx hardhat run scripts/deploy.js --network localhost   # terminal 2: prints address
```

Copy the printed address into the Laravel `.env`:

```
CHAIN_DRIVER=evm
CHAIN_RPC_URL=http://127.0.0.1:8545
CHAIN_CONTRACT_ADDRESS=0x...
CHAIN_DEPLOYER_KEY=0xac09...   # hardhat account #0, local only
```

`php artisan serve` in the app root, then create a point — the anchor goes on-chain.

## Notes

- Hardhat node forgets state on restart: redeploy and replay anchors
  (`php artisan tinker` loop over `Block` rows or re-create the points).
- `CHAIN_DRIVER=database` switches back to the offline hash-chain table, so the
  demo still works without Node running.
- The contract is permissionless on purpose: the app decides who may anchor;
  the chain only guarantees a stored hash can never be replaced.
