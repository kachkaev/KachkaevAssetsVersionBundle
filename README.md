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
    assets_version: v042
    # ...
```

then you can simply call ``app/console assets_version:increment`` to change ``v042`` to ``v043``. It is important to clear ``prod`` cache afterwards as this is not done automatically. More features are described below.

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
New to installing 3<sup>rd</sup> party bundles? Symfony docs will help:  
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

You don’t need to add anything to ```app/config/config.yml``` for it to apply.

### Option 1 (simple): Assets versioning is done on the server

If you are not using [AsseticBundle](https://symfony.com/doc/current/cookbook/assetic/index.html) for compressing your css and js files or if you call ```assetic:dump``` on the production server, you normally don’t want the changes of ```assets_version``` to show up in your git repository. All you have to do then is the following:

1. Modify your local copy of ```parameters.yml.dist```:

 ```yml
 parameters:
     # ...
     assets_version: v000
 ```

2. Enable ```%assets_version%``` in ```app/config/config.yml``` (see the top of this file)

3. Commit and push local changes (this will also include a new line in ```app/AppKernel.php``` and edits in both ```composer.json``` and ```composer.lock```)

4. Go to the server, ```git pull``` and ```composer install```. Since you have a new entry in ```parameters.yml.dist```, you will be asked to confirm that you want to copy ```assets_version: v000``` to ```app/config/parameters.yml```. You do. Press enter.

5. All done! Now each time you want to update the version of the assets, call these commands on the server:
 ```sh
app/console assets_version:increment --env=prod
app/console cache:clear --env=prod
# app/console assetic:dump --env=prod          # (if you are using assetic)
```

__Note:__ Replace ```app/console``` to ```bin/console``` if you are using Symfony3.

### Option 2 (recommended): Assets versioning is under the source control

If your app is running on multiple production servers or if you have a lot of css or js to compress with ```assetic:dump```, you will benefit from keeping compiled assets and their version under the git control.
In this case it takes a bit more time to prepare for the deploy, but the rest happens nearly instantly.
You won’t have to install UglifyCSS, UglifyJS or other filters on the production and will be able to switch to any stable version in a second.
Some cheap hostings struggle when compiling assets as this sometimes takes a lot of processor effort, so you are saving yourself from that potential problem too.

Since ```app/config/parameters.yml``` is in ```.gitignore```, ```assets_version``` cannot be stored there.

1. Create ```app/config/assets_version.yml``` and link to it from ```app/config/config.yml```

 ```yml
 # app/config/assets_version.yml
 parameters:
     assets_version: v000
```

 ```yml
 # app/config/config.yml
 imports:
     - { resource: assets_version.yml } # <- new line
     - { resource: parameters.yml }
     - { resource: security.yml }
     - { resource: services.yml }
 ```
 Do __not__ add ```app/config/assets_version.yml``` to ```.gitignore```!

2. Enable ```%assets_version%``` in ```app/config/config.yml``` (see the top of this file)

3. Add the following lines to ```app/config/config.yml```:
 ```yml
 kachkaev_assets_version:
     filename:  "%kernel.root_dir%/config/assets_version.yml"
  ```
4. That’s it, you are ready to commit what you’ve got! Now each time you want to update the assets on the server, follow this routine:

 _On the local machine:_
 ```sh
 app/console assets_version:increment
 app/console cache:clear --env=prod
 app/console assetic:dump --env=prod
 git commit
 ```

 _On the production server(s):_
 ```sh
 git pull
 ```

__Note:__ Replace ```app/console``` to ```bin/console``` if you are using Symfony3.

__Tip:__ Type less and do more by keeping common command sequences in shell scripts. Examples:

 ```sh
 # bin/refresh_prod (to be used on the local machine)
 #!/bin/sh

 PROJECT_DIR=$( cd "$( dirname "$0" )" && pwd )/..

 if [ "$1" = 'v' ];
 then
	$PROJECT_DIR/app/console assets_version:increment --env=prod
 fi

 $PROJECT_DIR/app/console cache:clear --env=prod
 $rm PROJECT_DIR/$/all.css
 $rm PROJECT_DIR/$/all.js
 $PROJECT_DIR/app/console assetic:dump --env=prod
 
 $cat PROJECT_DIR/app/config/assets_version.yml
```

 ```sh
 # bin/update_from_repo
 #!/bin/sh

 PROJECT_DIR=$( cd "$( dirname "$0" )" && pwd )/..

 cd $PROJECT_DIR & git pull
 cd $PROJECT_DIR & composer install --prefer-dist --optimize-autoloader
 rm -rf $PROJECT_DIR/app/cache/prod
 rm -rf $PROJECT_DIR/app/cache/dev
 $PROJECT_DIR/app/console cache:clear --env=prod
 ```

Console commands
----------------

The bundle adds two commands to the symfony console: ``assets_version:increment`` and ``assets_version:set``. Usage examples: 

```sh
# Increments assets version by 1 (e.g. was v1, became v2; was 0042, became 0043 - leading letters and zeros are kept)
$ app/console assets_version:increment

# Increments assets version by 10 (e.g. was v1, became v11; was 0042, became 0052)
$ app/console assets_version:increment 10

# Sets version to "1970-01-01_0000"
$ app/console assets_version:set 1970-01-01_0000

# Sets version to "abcDEF-something_else" (no numeric part, so assets_version:increment will stop working)
$ app/console assets_version:set abcDEF-something_else

# Decrements assets version by 10 (e.g. was 0052, became 0042; was lorem.ipsum.0.15, became lorem.ipsum.0.5)
# Note two dashes before the argument that prevent symfony from parsing -1 as an option name
$ app/console assets_version:increment -- -10

# Decrementing version by a number bigger than current version results 0 (e.g. was v0010, became v0000)
$ app/console assets_version:increment -- -1000
```

Value for assets version must consist only of letters, numbers and the following characters: ``.-_``. Incrementing only works when the current parameter value is integer or has a numeric ending.

Please don’t forget to clear cache by calling ``app/console cache:clear --env=prod`` for changes to take effect in the production environment.


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
