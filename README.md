# Sylius Wishlist Plugin

## Plugin Installation

### 1. Add repository to `composer.json`

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/michalkaczmarek1/sylius-wishlist-plugin.git"
    }
]
```

### 2. Set minimum stability to `dev`

```json
"minimum-stability": "dev"
```

### 3. Install the plugin via Composer

```bash
composer require sylius-academy/wishlist-plugin:dev-master
```

### 4. Ensure the plugin is registered in `config/bundles.php`

If not automatically added by Composer, add it manually.

### 5. Import the plugin configuration into `config/packages/_sylius.yaml`

```yaml
- { resource: "@SyliusAcademyWishlistPlugin/config/config.yaml" }
```

### 6. Import plugin routes in `config/routes.yaml`

```yaml
sylius_academy_wishlist_plugin:
    resource: "@SyliusAcademyWishlistPlugin/config/routes.yml"
```

### 7. Execute Sylius CLI commands

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
bin/console cache:clear
```

### 8. Build frontend assets

Test it in environment where is installed node eg. docker container.

```bash
yarn install
yarn encore dev
```

> **Note:** Ensure that all changes have been properly implemented and test the wishlist functionality on both frontend and backend.

