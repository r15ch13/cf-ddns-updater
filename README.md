# Cloudflare DDNS updater
Mini Dynamic DNS Service using Cloudflare

## Installation
```bash
λ git clone <this-repo>
λ composer install
```

## Usage
```
λ curl 'https://ddns.example.org/?zone=example.org&domain=home.example.org&wildcard=true' \
  -H 'X-Auth-Email: <cloudflare email>' \
  -H 'X-Auth-Key: <cloudflare key>
```

- `zone` = Cloudflare Zone Name (not the ID)
- `domain` = Domain or Subdomain
- `wildcard` = Use *.example.org
- `ip|ipv4` = Update ipv4 (no auto detection)
- `ipv6` = Update ipv6

The username and password field of your FRITZ!Box settings will be used.

FRITZ!Box Example: `https://ddns.example.org/?zone=example.org&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>`

# License
[The MIT License (MIT)](http://r15ch13.mit-license.org/)
