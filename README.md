### Node Proxy

A quick script to proxy web3 node requests so that we can;

- hide api keys
- do filtering on allowed rpc methods to prevent abuse
- do filtering on request origins to prevent abuse

### Installation

#### Heroku

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://dashboard.heroku.com/new?template=https%3A%2F%2Fgithub.com%2Fkyber-community-staking-protocol%2Fnode-proxy)

```
heroku config:set INFURA_ID="XXX"
heroku config:set ORIGIN_DOMAIN="XXX"
heroku config:set RPC_METHODS="eth_getBlockByNumber|eth_blockNumber|eth_call|eth_getBalance|eth_gasPrice"
```