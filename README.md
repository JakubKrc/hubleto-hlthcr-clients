# Hlthcr Clients

Clients (employers) of an occupational health service on Hubleto Customers: client types, audit interval, e-mail export.

Hubleto app of the Hlthcr set (occupational health service, Slovakia). Namespace: `Hubleto\App\External\WaiBlue\HlthcrClients`.
Needs: `wai-blue/hubleto-hlthcr-core`.

## Install into a Hubleto project

```bash
composer config repositories.wai-blue/hubleto-hlthcr-clients vcs <repository-url>
composer require wai-blue/hubleto-hlthcr-clients:dev-main
php hubleto app install "Hubleto\App\External\WaiBlue\HlthcrClients"
npm run build
```
