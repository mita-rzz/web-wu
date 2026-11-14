1. Menyalakan dan merakit mesin docker

```
docker compose -f compose.dev.yaml up -d --build

```

2. menjalankan migrasi database

```
docker compose -f compose.dev.yaml exec app php artisan migrate
```

- memeriksa lingkungan (environment) aplikasi kamu saat ini

```
docker compose -f compose.dev.yaml exec app php artisan env
```

- Cara membuat file baru untuk migrate tabel

```
docker compose -f compose.dev.yaml exec app php artisan make:migration create_nama_tabel
```

- Membuka terminal CLI Database:

```
docker compose -f compose.dev.yaml exec app php artisan db
```

- menjalankan npm di container node

```
docker compose -f compose.dev.yaml run --rm --service-ports node
```

docker compose -f compose.dev.yaml exec app php artisan db:show

START

```
docker compose -f compose.dev.yaml exec app rm -f public/hot
docker compose -f compose.dev.yaml exec node npm run build
```

git archive --format=zip --output=../v12.zip HEAD
