# Node Proxy

A quick script to proxy web3 node requests so that we can;

- hide api keys
- do filtering on allowed rpc methods to prevent abuse
- do filtering on request origins to prevent abuse

`php -S localhost:9001 ./src/index.php`

## Installation

### Heroku

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://dashboard.heroku.com/new?template=https%3A%2F%2Fgithub.com%2Fkyber-community-staking-protocol%2Fnode-proxy)

```
heroku config:set INFURA_ID="XXX"
heroku config:set ANYBLOCK_ID="XXX"
heroku config:set RIVET_ID="XXX"
heroku config:set QUIKNODE_ID="XXX"
heroku config:set ORIGIN_DOMAIN="XXX"
heroku config:set RPC_METHODS="eth_getBlockByNumber|eth_blockNumber|eth_call|eth_getBalance|eth_gasPrice"
heroku config:set WEIGHTED_ROUTES="INFURA{1}|ANYBLOCK{2}|RIVET{2}|QUIKNODE{1}"
```

#### Weighted Routing

We can configure the weighted routing to favour a particular endpoint. This is set in environmental variable `WEIGHTED_ROUTES`.
Example: `INFURA{1}|ANYBLOCK{2}|RIVET{2}` will favour Infura 20% of the time, Anyblock 40% of the time, Rivet 40% of the time.
Note: Ensure these numbers are whole integeters. You can set any value you want - ie: you can make all the numbers add to 100 for easier reading on favoured weighting.