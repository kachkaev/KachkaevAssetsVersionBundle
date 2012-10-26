KachkaevAssetsVersionBundle
===========================

Overview
--------

The purpose of this Symfony2 bundle is to automate the process of updating assets version each time it needs to be changed (doing this manually is a real pain).

The bundle can read and write ``assets_version`` parameter in ``app/config/parameters.yml`` from symfony console. One of the advantages of the bundle is that it does not break existing formatting in the yaml file.

So, if you configuration looks the following way:

```yml
# app/config/config.yml
framework:
    templating:      { engines: ['twig'], assets_version: %assets_version% }
    # ...
```

```yml
# app/config/parameters.yml
parameters:
    assets_version: v42
    # ...
```

then you can simply call ``php app/console assets_version:increment`` to change version ``v42`` to ``v43``. It is important to clear ``prod`` cache afterwards, this is not done automatically. More features are described below.

Installation
------------

### Composer

Add the following dependency to your project’s composer.json file:

```js
    "require": {
        // ...
        "kachkaev/assets-version-bundle": "dev-master"
        // ...
    }
```
Now tell composer to download the bundle by running the command:

```bash
$ php composer.phar update kachkaev/assets-version-bundle
```

Composer will install the bundle to `vendor/kachkaev` directory.

### Adding bundle to your application kernel

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Kachkaev\AssetsVersionBundle\KachkaevAssetsVersionBundle(),
        // ...
    );
}
```

Configuration
-------------

Here is the default configuration for the bundle:

```yml
kachkaev_assets_version:
    filename: %kernel.root_dir%/config/parameters.yml          # name of the file where application parameters are stored
    parametername: assets_version                              # name of property that defines assets version in that file
    manager: Kachkaev\AssetsVersionBundle\AssetsVersionManager # location of version manager
```

In most cases custom configuration is not needed, so simply add the following line to your ``app/config/config.yml``:

```yml
kachkaev_assets_version: ~
```

Usage
-----

The bundle adds two commands to symfony console: ``assets_version:increment`` and ``assets_version:set`` that are incrementing and setting assets version, respectively. Usage examples: 

```bash
# Increments assets version by 1 (e.g. was v42, became v43)
$ php app/console assets_version:increment

# Increments assets version by 10 (e.g. was 42, became 52)
$ php app/console assets_version:increment 10

# Sets version to "1970-01-01_0000"
$ php app/console assets_version:set 1970-01-01_0000

# Sets version to "abcDEF-something_else"
$ php app/console assets_version:set abcDEF-something_else

# Decrements assets version by 10 (e.g. was lorem.ipsum.0.42, became lorem.ipsum.0.32)
# Note two dashes before the argument that prevent symfony from parsing -1 as an option
$ php app/console assets_version:increment -- -10
```

Value for assets version must consist only of letters, numbers and the following characters: ``.-_``. Incrementing only works when existing value is integer or has integer ending.

Please don’t forget to clear product cache by calling ``php app/console cache:clear --env=prod`` for changes to take effect in the production environment.

If you are using assetic bundle and want to change asset version after each dump, you may find useful the following shell script:

```bash
# bin/update_assets
php app/console assets_version:increment --env=prod
php app/console cache:clear --env=prod --no-debug
php app/console assetic:dump --env=prod --no-debug
```