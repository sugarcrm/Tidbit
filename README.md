Tidbit v2.0
===========
Tidbit is random data generator for SugarCRM versions 5.5 and later.  By optimizing
the communications with the database, large amounts of data can be inserted
into the system for testing without manual intervention.

Requirements
------------
PHP 5.3+ (could work for older versions, but no guaranty)
SugarCRM Already installed

Installation
------------
To install Tidbit, unpack the Tidbit-v###.tar.bz2 file, and place the Tidbit/
directory within your SugarCRM installation (Tidbit Directory need to be created inside SugarCRM Installation folder).

The only requirement of Tidbit is that you have an installed and properly
configured copy of SugarCRM in the directory above it.


Usage
-----
**NOTE** **Usage of Tidbit could affect on your _data_ in DB**
Please make sure you have a backup, before running data Generation commands

Tidbit uses a command line interface.  To run it from the Tidbit directory:

    $ php -f install_cli.php

Various options are available to control the number of entries generated.
To view them:

    $ php -f install_cli.php -- -h

Example usages:

    * Clean out existing seed data when generating new data set:
      $ php -f install_cli.php -- -c

    * Insert 500 users:
      $ php -f install_cli.php -- -u 500
      
    * Generate data into csv (mysql is default):
      $ php -f install_cli.php -- --storage csv

    * Obliterate all data when generating new records with 300 users:
      $php -f install_cli.php -- -o -u 400
      
    * Create data using a load factor of 10, automatically detecting modules
      and automatically adding relationships.
      $php -f install_cli.php -- -l 10 -o --allmodules --allrelationships
      
    * Generate TeamBasedACL action restrictions for chosen level (check level options in install_config.php)
      $php -f install_cli.php -- -o --tba -tba_level full
      
    * Controlling INSERT_BATCH_SIZE (MySQL Support only for now)
      $php -f install_cli.php -- -o --insert_batch_size 1000

Contributing:
------------
See [CONTRIBUTING](CONTRIBUTING.md) for how you can contribute changes back into this project.
