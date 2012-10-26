Overview
--------

The main objective of this Symfony2 bundle is to automate the process of updating assets version each time it needs to be changed − doing this manually is a real pain.

The bundle can read and write ``assets_version`` parameter in ``app/config/parameters.yml`` from symfony console. One of the advantages of the bundle is that it does not break existing formatting in the yaml file.

So, if you configuration looks the following way:

    # app/config/config.yml
    framework:
        templating:      { engines: ['twig'], assets_version: %assets_version% }
        # ...

    # app/config/parameters.yml
    parameters:
        assets_version: v42
        # ...

then you can simply call ``php app/console assets_version:increment`` to change version ``v42`` to ``v43``. It is important to clear ``prod`` cache afterwards, this is not done automatically. Additional features are described below.

Installation
------------

### Composer

Add the following dependencies to your projects composer.json file:

    "require": {
        # ...
        "kachkaev/assets-version-bundle": "dev-master"
        # ...
    }

### Adding bundle to your application kernel

    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Kachkaev\AssetsVersionBundle\KachkaevAssetsVersionBundle(),
            // ...
        );
    }

Configuration
-------------

Here is the default configuration for the bundle:

    kachkaev_assets_version:
        filename: %kernel.root_dir%/config/parameters.yml          # name of the file where application parameters are stored
        parametername: assets_version                              # name of property defining assets version in that file
        manager: Kachkaev\AssetsVersionBundle\AssetsVersionManager # Class of version manaer

In most cases changing of it is not needed; simply add the following line to your ``app/config/config.yml``:

    kachkaev_assets_version: ~

Usage
-----

The bundle adds two commands to symfony console: ``assets_version:increment`` and ``assets_version:set`` that are incrementing and setting assets version, respectively. Usage examples: 

    # Increments assets version by 1 (e.g. was v42, became v43)
    $ php app/console assets_version:increment
    
    # Increments assets version by 10 (e.g. was 42, became 52)
    $ php app/console assets_version:increment --delta=10
    
    # Sets version to "1970-01-01_0000"
    $ php app/console assets_version:set 1970-01-01_0000

    # Sets version to "abcDEF-something_else"
    $ php app/console assets_version:set abcDEF-something_else

Value for assets version must consist only of letters, numbers and the following characters: ``.-_``. Incrementing only works when existing value is integer or has integer ending.

Please don’t forget to clear product cache by calling ``php app/console cache:clear --env=prod`` for changes to take effect in the production environment.

If you are using assetic bundle and want to change asset version after each dump, you may find useful the following shell script:

    # bin/update_assets
    php app/console assets_version:increment --env=prod
    php app/console cache:clear --env=prod --no-debug
    php app/console assetic:dump --env=prod --no-debug