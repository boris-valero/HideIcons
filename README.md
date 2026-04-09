# AppOrder

AppOrder is a Nextcloud customization app that lets administrators:

- Hide application icons from the top app menu
- Reorder application icons with drag and drop
- Apply the same menu customization rules instance-wide

As an administrator, you can configure the appearance of the Nextcloud menu for your users to suit your preferences.

## Requirements

- Nextcloud `>= 32` and `<= 34`
- PHP `^8.2 or higher`
- Node.js `^22 or higher`

## Installation

### Production (Nextcloud app deployment)

1. Place this app in your Nextcloud `apps/` directory as `apporder`.
2. Install dependencies and build:

```bash
composer install && npm install && npm run build
```

3. Enable it:

```bash
php occ app:enable apporder
```

## Usage

1. Open Nextcloud Admin settings.
2. Go to the AppOrder section.
3. Reorder rows to change top menu order.
4. Toggle switches to hide/show app icons.
5. Click Save.

## Localization

Available languages:

- English
- French

## License

AGPL-3.0-or-later

## Author

Boris Valero

