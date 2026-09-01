# Kavach — License & Update Server

Central license (serial key) + software update server for all products:
OpenPharma, OpenVyapar, OpenRetail, and anything future. One server, many products.

**Stack:** Laravel 12 · Filament v3 admin · SQLite (dev) / MySQL (production) · Ed25519 signed responses (libsodium / sodium_compat)

---

## What it does

| Feature | How |
|---|---|
| Lifetime / monthly / yearly plans | `plans.type` + `licenses.expires_at` (`null` = lifetime) |
| 30-day trial | A plan with `type=trial, duration_days=30` |
| Extend a trial / any license | Licenses table → **Extend** action → enter days |
| Normal vs Pro features | `licenses.tier`, returned in every signed payload; apps call `$kavach->isPro()` |
| Activation limit | `licenses.max_activations` — each install sends a machine/domain fingerprint; extra installs get `MAX_ACTIVATIONS` |
| Suspend a non-paying customer | Licenses table → **Suspend** (instant, remote) |
| Software updates | Upload a zip in **Releases**; apps auto-discover it via `/api/v1/update/check`, download over a 30-min signed URL, verify sha256 |
| Anti-tamper | Every license/update response is Ed25519-signed; clients verify with the embedded public key. Cached license files that are edited become invalid. |
| Offline shops | Clients revalidate once per day with an 8-day offline grace window |

## Dev quickstart

```
composer install
php artisan migrate --seed        # seeds OpenPharma + plans + demo trial license
php artisan kavach:keys           # prints the public key for client apps
php artisan serve --port=8901
```

Admin panel: `/admin` — login `electroiot.in@gmail.com` / `kavach@2026` (CHANGE IN PRODUCTION).

## API (all JSON, `/api/v1`)

| Endpoint | Purpose |
|---|---|
| `POST /activate` | `{product, license_key, fingerprint, label?, app_version?}` → signed license payload |
| `POST /validate` | daily heartbeat, same payload |
| `POST /deactivate` | frees the activation slot |
| `POST /update/check` | `{product, license_key, fingerprint, current_version, channel?}` → signed update info + download URL |
| `GET /update/download/...` | signed URL only, streams the zip, logs the download |

Success responses: `{ok, payload (base64 json), signature (base64 Ed25519)}`.
Errors: `{ok:false, code, message}` — codes: `INVALID_KEY`, `EXPIRED`, `SUSPENDED`, `MAX_ACTIVATIONS`, `NOT_ACTIVATED`, `LICENSE_INACTIVE`.

## Integrating a product (e.g. OpenPharma)

1. Copy `client-sdk/KavachClient.php` into the app.
2. Configure it with the server URL, product slug, **public key** (from `php artisan kavach:keys`), a writable storage dir, and the app version.
3. Gate the app with `$kavach->check()`; show an activation form that calls `$kavach->activate($key)`.
4. Feature-gate with `$kavach->isPro()`, show trial banners with `$kavach->isTrial()` / `daysLeft()`.
5. Updates: `checkUpdate()` → `downloadUpdate()` → `installUpdate($zip, $appRoot, $protectedPaths)`.

See `client-sdk/example.php` for a full walkthrough.
If a customer's host lacks the sodium extension, ship `paragonie/sodium_compat` with the app.

## Publishing an update

1. Zip the new app files (exclude `.env`, uploads, storage — the client also protects these).
2. Admin → **Releases** → New: pick product, set a higher semver (e.g. `1.1.0`), paste changelog, upload zip.
3. sha256 + size are computed automatically. Every valid license sees it on its next update check.
4. `Minimum tier = Pro` makes a release Pro-only; `channel = beta` hides it from stable users.

## Production deploy (Hostinger)

1. Create subdomain (e.g. `kavach.yourdomain.in`), upload project, point docroot to `public/`.
2. Switch `.env` to MySQL, run `php artisan migrate --seed`, `php artisan kavach:keys`.
3. Set `APP_ENV=production`, `APP_DEBUG=false`, change the admin password.
4. Keep `storage/app/private/keys/signing.key` secret — it is the master key. Never commit it, never share it.

## Not built yet (next phase)

- Razorpay checkout page + webhooks (auto-issue keys on payment, suspend on failed subscription renewal). Until then, issue keys manually from Filament and record payments in the Payments resource.
- Email the key to the customer on license creation.
- Customer self-service portal (invoices, manage activations).
