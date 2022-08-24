# README

Issue reproduction tests for LT.


## Requirements

1. PHP 7.4


## Installation

1. Check out this repo
2. Get composer from https://getcomposer.org/download/
3. Run `composer install`
4. Copy .env.dist file to .env
5. Set correct hub urls .env


## Running tests

Viewing available testcases
```
php application.php
 ```


# Pixel 6 dimensions

This test verifies that all screenshots and viewports are of the same size. Will output any screenshots that do not
match.

Output is stored in `output/pixel-6-dimensions`. Remove all files in dir to reset test.

```
 php application.php test:pixel-6-dimensions
```
