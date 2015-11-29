KachkaevAssetsVersionBundle
===========================

[![Build Status](https://secure.travis-ci.org/kachkaev/KachkaevAssetsVersionBundle.png)](http://travis-ci.org/kachkaev/KachkaevAssetsVersionBundle)
[![Latest Stable Version](https://poser.pugx.org/kachkaev/assets-version-bundle/v/stable)](https://packagist.org/packages/kachkaev/assets-version-bundle)
[![Total Downloads](https://poser.pugx.org/kachkaev/assets-version-bundle/downloads)](https://packagist.org/packages/kachkaev/assets-version-bundle)
[![License](https://poser.pugx.org/kachkaev/assets-version-bundle/license)](https://packagist.org/packages/kachkaev/assets-version-bundle)

Updating the assets version manually at each deploy is a real pain. The purpose of this Symfony2 & Symfony3 bundle is to automate this process and thus to make your life a bit happier.

The bundle can read and write ``assets_version`` parameter in ``app/config/parameters.yml`` (or any other ``*.yml`` file) from the Symfony console. The original file formatting is carefully preserved, so you won’t lose the comments or empty lines between the groups of parameters, if there are any.

If the configuration of your project looks the following way:

```yml
# app/config/config.yml

## Symfony >=2.7, >=3.0
framework:
    # ...
    assets:
        version: "%assets_version%"

## Symfony <=2.6
framework:
    # ...
    templating:      { engines: ['twig'], assets_version: "%assets_version%" }
    # ...
```

```yml
# app/config/parameters.yml
parameters:
    # ...
    assets_version: v42
    # ...
```

then you can simply call ``php app/console assets_version:increment`` to change ``v42`` to ``v43``. It is important to clear ``prod`` cache afterwards as this is not done automatically. More features are described below.

The reasons for versioning your project’s assets are listed in the Symfony docs:  
http://symfony.com/doc/current/reference/configuration/framework.html#ref-framework-assets-version


Installation
------------

Run ```composer require kachkaev/assets-version-bundle```

Register the bundle in ``app/AppKernel.php``

```php
$bundles = array(
    // ...
    new Kachkaev\AssetsVersionBundle\KachkaevAssetsVersionBundle(),
    // ...
);
```
Not sure how to install 3<sup>rd</sup> party bundles? Symfony docs will help:  
http://symfony.com/doc/current/cookbook/bundles/installation.html


Configuration
-------------

Here is the default configuration for the bundle:

```yml
assets_version:

    # the name of the file that contains the assets version parameter
    filename:             '%kernel.root_dir%/config/parameters.yml'

    # the name of the parameter to work with
    parametername:        assets_version

    # the name of the class that reads and writes the assets version parameter
    manager:              Kachkaev\AssetsVersionBundle\AssetsVersionManager
```

### Option 1 (simple): Assets versioning is done on the server

If you are not using [AsseticBundle](https://symfony.com/doc/current/cookbook/assetic/index.html) for compressing your css and js files or if you call ```assetic:dump``` on the production server, you normally don’t want the changes of ```assets_version``` to show up in your git repository. All you have to do then is the following:

1. Modify your local copy of ```parameters.yml.dist```:

 ```yml
parameters:
    # ...
    assets_version: v000
```

2. Commit and push local changes (this will also include a new line in ```app/AppKernel.php``` and edits in ```composer.json``` and ```composer.lock```)

3. Go to the server, ```git pull``` and ```composer install```. Since you have a new entry in ```parameters.yml.dist```, you will be asked to confirm that you want to copy ```assets_version: v000``` to ```app/config/parameters.yml```. Press enter.

4. Each time you want to update the version of the assets, call these commands on the server:
 ```sh
php app/console assets_version:increment --env=prod
php app/console cache:clear --env=prod
# app/console assetic:dump --env=prod  # (if you are using assetic)
```

__Note:__ Change ```app/console``` to ```bin/console``` if you are using Symfony3.

### Option 2 (recommended): Assets versioning is under the source control

(TODO)

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
