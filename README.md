KachkaevAssetsVersionBundle
===========================

The purpose of this Symfony2 bundle is to automate the process of updating assets version each time it needs to be changed − doing this manually is a real pain.

The bundle can read and write ``assets_version`` parameter in ``app/config/parameters.yml`` from Symfony console. One of the advantages of the bundle is that it does not break existing formatting in the yaml file, all user comments are also kept.

So, if you configuration looks the following way:

```yml
# app/config/config.yml

## Symfony <=2.6
framework:
    templating:      { engines: ['twig'], assets_version: %assets_version% }
    # ...

## Symfony >=2.7
framework:
    assets:
        version: %assets_version%
    # ...
```

```yml
# app/config/parameters.yml
parameters:
    assets_version: v42
    # ...
```

then you can simply call ``php app/console assets_version:increment`` to change version from ``v42`` to ``v43``. It is important to clear ``prod`` cache afterwards as this is not done automatically. More features are described below.

Detailed information on using assets versioning can be found in Symfony2 documentation: http://symfony.com/doc/current/reference/configuration/framework.html#ref-framework-assets-version

[![Build Status](https://secure.travis-ci.org/kachkaev/KachkaevAssetsVersionBundle.png)](http://travis-ci.org/kachkaev/KachkaevAssetsVersionBundle)

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
__Note:__ It is not recommended to store real value of assets version in ``config.yml`` because its incrementing  will cause git conflicts. It is better to keep it in ``parameters.yml`` added to ``.gitignore`` and also have ``parameters.yml.dist`` with blank or initial value for assets version.

Usage
-----

The bundle adds two commands to symfony console: ``assets_version:increment`` and ``assets_version:set`` that are incrementing and setting assets version, respectively. Usage examples: 

```bash
# Increments assets version by 1 (e.g. was v42, became v43)
$ php app/console assets_version:increment

# Increments assets version by 10 (e.g. was 42, became 52; was 0042, became 0052 - leading zeros are kept)
$ php app/console assets_version:increment 10

# Sets version to "1970-01-01_0000"
$ php app/console assets_version:set 1970-01-01_0000

# Sets version to "abcDEF-something_else" (no numeric part, so assets_version:increment will stop working)
$ php app/console assets_version:set abcDEF-something_else

# Decrements assets version by 10 (e.g. was lorem.ipsum.0.15, became lorem.ipsum.0.5)
# Note two dashes before the argument that prevent symfony from parsing -1 as an option
$ php app/console assets_version:increment -- -10

# Decrementing version by a number bigger than current version results 0 (e.g. was v0010, became v0000)
$ php app/console assets_version:increment -- -1000
```

Value for assets version must consist only of letters, numbers and the following characters: ``.-_``. Incrementing only works when existing value is integer or has integer ending.

Please don’t forget to clear cache by calling ``php app/console cache:clear --env=prod`` for changes to take effect in the production environment.

If you are using assetic bundle on your production server and want to change asset version at each dump automatically, you may find useful the following shell script:

```bash
# bin/update_assets
php app/console assets_version:increment --env=prod
php app/console cache:clear --env=prod
php app/console assetic:dump --env=prod
```

At the moment the bundle only works with yaml files, but more file types can be added if there is a demand.

Capifony integration
--------------------

If you are using [Capifony](http://capifony.org) you can automate increment of `assets_version` during deployment using such code placed in `deploy.rb`:

```ruby
before "symfony:cache:warmup", "assets_version:increment", "symfony:cache:clear"

namespace :assets_version do
  task :increment do
    capifony_pretty_print "--> Increase assets_version"

    run "#{latest_release}/app/console assets_version:increment --env=#{symfony_env_prod}"

    capifony_puts_ok
  end
end
```
