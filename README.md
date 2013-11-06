# eZ Publish configurator


A way to configure ezpublish 4 installations, when using a multi stage environment for instance int, stage or prod.

Provides:

* Avoid duplication of site access configurations, that differ only in some parts.
* Keeps sensitive data like database credentials, api keys out of scm.

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
settings:
    # The settings file to override
    override/site.ini.append.php:
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
```

## Usage

Executing the CLI script:

```shell
 $ php bin/configurator update-configuration prod-env.yml /var/www/ezpublish
 ```
