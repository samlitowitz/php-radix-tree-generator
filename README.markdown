# PHP Radix Tree Generator

Generate PHP code for a Radix Tree for a static set of data

## Table of Contents

1. [Installation](#installation)
2. [Usage](#usage)
    1. [Configuration](#configuration)

## Installation

Install via [Composer](https://getcomposer.org/).

```shell
composer require --dev samlitowitz/php-radix-tree-generator
```

## Usage

1. Create a [configuration](#configuration) targeting the type(s) you wish to generate a collection for.
2. Generate the desired collection(s) by running the following command

   ```shell
   ./vendor/bin/php-radix-tree generate config.json ./path/to/dir/
   ```

### Configuration

A JSON schema for the configuration is available [here](assets/schema/configuration.json).
The example [`RadixTree`](examples/iso-3166-2/RadixTree.php) is generated using the
example [configuration](examples/iso-3166-2/config.json).

---
This site or product includes IP2Location™ ISO 3166-2 Subdivision Code which available from https://www.ip2location.com.
