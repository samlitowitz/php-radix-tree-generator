#!/usr/bin/env bash

export XDEBUG_MODE=debug XDEBUG_SESSION=1
php ../../bin/php-radix-tree generate config.json $(pwd)
