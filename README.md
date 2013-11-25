# eZ Publish configurator


A way to configure ezpublish 4 installations, when using a multi stage environment for instance int, stage or prod.

Provides:

* Avoid duplication of site access configurations, that differ only in some parts.
* Keeps sensitive data like database credentials, api keys out of scm.
* No kernel hacks.

Requirements:

* There is a dependency to eZ Publish 4 `\eZINI` class, that currently cannot be covered by composer. So you must point to a valid eZ document root (see usage).

## Install


Add the library to your project (e.g. via [Composer](http://getcomposer.org/))

 ```json
 {
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/p7s1digital/ezpublish-configurator"
        }
    ],
    "require": {
        "psd/ezpublish-configurator": "dev-master"
    }
 }
 ```

## Sample Configuration

Here is a small configuration file that holds sensitive database credentials.

```yaml
# The settings file to override
settings/override:
    site.ini.append.php:
        # Defines block values that can be overwritten.
        DatabaseSettings:
            # The variable that can be overwritten.
            Server: 127.0.0.1
            Port: 3306
            User: dbuser
            Password: dbpassword
            Database: ez_db
            Charset: utf8
            Socket: /var/lib/mysql/mysql.sock
extension/myextension/settings:
    site.ini.append.php:
        MyBlock:
            MyVar: true
```

## Usage

Executing the CLI script:

```shell
 $ php bin/configurator update-configuration prod-env.yml /var/www/ezpublish
 ```
