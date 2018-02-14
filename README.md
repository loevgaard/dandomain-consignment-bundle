# Dandomain Consignment Bundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]

Symfony bundle to handle consigment manufacturers in Dandomain.

## Installation

### Step 1: Install dependencies

This bundle depends on the [Doctrine2 Behaviors Bundle by KNP Labs](https://github.com/KnpLabs/DoctrineBehaviors).

Install that bundle first, and then return to this page.

### Step 2: Download the bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require loevgaard/dandomain-stock-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 3: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Loevgaard\DandomainConsignmentBundle\LoevgaardDandomainConsignmentBundle(),
        ];

        // ...
    }

    // ...
}
```

### Step 4: Configure the bundle
```yaml
# app/config/config.yml

```

### Step 5: Update your database schema
```bash
$ php bin/console doctrine:schema:update --force
```

or use [Doctrine Migrations](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html).

[ico-version]: https://img.shields.io/packagist/v/loevgaard/dandomain-consignment-bundle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/loevgaard/dandomain-consignment-bundle/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/loevgaard/dandomain-consignment-bundle.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/loevgaard/dandomain-consignment-bundle.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/loevgaard/dandomain-consignment-bundle
[link-travis]: https://travis-ci.org/loevgaard/dandomain-consignment-bundle
[link-scrutinizer]: https://scrutinizer-ci.com/g/loevgaard/dandomain-consignment-bundle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/loevgaard/dandomain-consignment-bundle