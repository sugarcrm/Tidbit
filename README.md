Tidbit v3.0 (bleeding edge) -- [Tidbit v2.0 (stable)](https://github.com/sugarcrm/Tidbit/tree/v2.0.0) 
===========

| [Master][Master] | [Develop][Develop] |
|:----------------:|:----------:|
| [![Build status][Master image]][Master] | [![Build status][Develop image]][Develop] |
| [![Coverage Status][Master coverage image]][Master coverage] | [![Coverage Status][Develop coverage image]][Develop coverage] |

Tidbit is random data generator for Sugar versions 7.8 and later.  By optimizing
the communications with the database, large amounts of data can be inserted
into the system for testing without manual intervention.

Documentation in [the wiki](https://github.com/sugarcrm/Tidbit/wiki)!
------------------
**Please visit [the wiki](https://github.com/sugarcrm/Tidbit/wiki) for detailed documentation on using and configuring Tidbit.**

Requirements
------------
PHP 5.6+
Sugar Already installed (7.8+ versions)

Installation
------------
To install Tidbit, unpack the Tidbit-v###.tar.bz2 file, and place the Tidbit/
directory within your SugarCRM installation (Tidbit Directory need to be created inside SugarCRM Installation folder).

Download composer
```
curl -sS https://getcomposer.org/installer | php
```

Install composer dependencies inside Tidbit directory
```
./composer.phar install
```

The only other requirement of Tidbit is that you have an installed and properly
configured copy of Sugar in the directory above it.

Installation of Vagrant Stack (Example):
------------

1. SSH into vagrant stack you are using via command line.
    ```
    $ vagrant ssh
    ```

2. Navigate to Sugar directory. 
    ```
    $ cd /
    $ cd var/www/html/ (sugar instance).
    ```
    
3. Download zip file from repo (master.zip) into the sugar instance. e.g. /SugarEnt-Full-7.6.0.0/.
    ```
    $ wget (url to zip file)
    ```
    
4. Unzip file (master.zip). Directory created by zip file is called Tidbit-master.
    ```
    $ unzip master.zip
    ```
    
5. Change Directory to /Tidbit e.g., mv /Tidbit-master /Tidbit.
    ```
    $ mv /Tidbit-master /Tidbit
    ```
    
6. Navigate to /Tidbit and follow instructions under usage, below, from within /Tidbit directory.
    ```
    $ cd /Tidbit
    ```
    
7. Install Composer dependencies
    ```
    $ ./composer.phar install
    ```
    
Configuration
-------------
    Tidbit has default config files in tidbit_root/config/ folder.
    Here:
        - config.php          -- main config file
        - data/*.php          -- customization of filling for separate fields if
                                 bean tables
        - relationships/*.php -- customization of filling for separate fields if
                                 bean relation tables
    Tidbit settings can be fully overrided in tidbit_root/custom/config.php file
    by php arrays in the same manner as in original configs

Usage
-----
**NOTES:** 

* Usage of Tidbit could affect your _data_ in DB.
Please make sure you have a backup, before running data Generation commands

* In case of generation csv (--storage csv).
We suppose what csv-dump will be used on empty DB, so for speed up, we'll generate
values (integer starting with 1) for autoincrement-type fields.

* Following mysql configuration can decrease generation time:
```conf
[mysqld]
innodb_doublewrite = 0
innodb_support_xa = 0
innodb_buffer_pool_size = 3G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 0
max_allowed_packet = 1024M
```

Tidbit uses a command line interface.  To run it from the Tidbit directory:

    $ ./bin/tidbit (or ./vendor/bin/tidbit for package dependency installation)

Various options are available to control the number of entries generated.
To view them:

    $ ./bin/tidbit -h

Example usages:

    * Clean out existing seed data when generating new data set:
      $ ./bin/tidbit -c

    * Insert 500 users:
      $ ./bin/tidbit -u 500
      
    * Generate data into csv (mysql is default):
      $ ./bin/tidbit --storage csv

    * Generate records for all out-of-box and custom modules, plus find all relationships
      $ ./bin/tidbit --allmodules --allrelationships

    * Obliterate all data when generating new records with 400 users:
      $ ./bin/tidbit -o -u 400

    * Use profile (pre-existing one: simple, simple_rev2, average, high) file to generate data
      $ ./bin/tidbit -o --profile simple --sugar_path /some/sugar/path

    * Use custom profile (located in /path/to/profile/file)
      $ ./bin/tidbit -o --profile /path/to/profile --sugar_path /some/sugar/path

    * Generate TeamBasedACL action restrictions for chosen level (check level options in config files)
      $ ./bin/tidbit -o --tba -tba_level full
      
    * Controlling INSERT_BATCH_SIZE (MySQL Support only for now)
      $ ./bin/tidbit -o --insert_batch_size 1000

    * Setting path to SugarCRM installation
      $ ./bin/tidbit -o --sugar_path /some/sugar/path

    * Using DB2 storage example (mysql/oracle/db2 can be used, depending on Sugar installation and DB usage)
      $ ./bin/tidbit -o --sugar_path /some/sugar/path --storage db2

Contributing:
------------
See [CONTRIBUTING](CONTRIBUTING.md) for how you can contribute changes back into this project.

All Pull Requests should be targeted to "develop" branch and follow PSR2 Code Style Standard
To run quick code check use

    $ ./composer.phar check-style

or call PHP CS directly

    $ ./vendor/bin/phpcs --standard=./ruleset.xml
    
to run PHPUnit tests locally please use

    $ ./composer.phar tests
    
or call PHPUnit directly

    $ ./vendor/bin/phpunit -c ./phpunit.xml.dist
    
There are automated PR checks enabled on TravisCI (https://travis-ci.org/sugarcrm/Tidbit)
For each PR code-style and phpunit tests will be executed for verification

  [Master image]: https://api.travis-ci.org/sugarcrm/Tidbit.svg?branch=master
  [Master]: https://travis-ci.org/sugarcrm/Tidbit
  [Master coverage image]: https://coveralls.io/repos/github/sugarcrm/Tidbit/badge.svg?branch=master
  [Master coverage]: https://coveralls.io/github/sugarcrm/Tidbit?branch=master
  [Develop image]: https://api.travis-ci.org/sugarcrm/Tidbit.svg?branch=develop
  [Develop]: https://github.com/sugarcrm/Tidbit/tree/develop
  [Develop coverage image]: https://coveralls.io/repos/github/sugarcrm/Tidbit/badge.svg?branch=develop
  [Develop coverage]: https://coveralls.io/github/sugarcrm/Tidbit?branch=develop
