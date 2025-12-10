# plenty-shopware6-connector

Lokal Shopware 6 ortamı (Dockware) ve Plentymarkets entegrasyon plugin’i.

## Hızlı başlat (Dockware)

1. `docker compose up -d` çalıştır.
2. Container’da Shopware hazır olduğunda admin: `http://localhost/admin`, vitrin: `http://localhost`.
3. Plugin dizini otomatik mount: `/var/www/html/custom/plugins/PlentyConnector`.
4. Container içine girip plugin’i kur/aktif et:
   - `docker exec -it dockware-shopware6 bash`
   - `bin/console plugin:refresh`
   - `bin/console plugin:install --activate PlentyConnector`
   - `bin/console cache:clear`

Plentymarkets erişim bilgilerini Shopware admin → Settings → Plugins → PlentyConnector ayarından gir.

## Manuel ürün senkronu

```
docker exec dockware-shopware6 bash -lc "bin/console plenty:sync:products"
```
