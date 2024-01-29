# PHP-Config
[![Packgist](https://img.shields.io/packagist/v/carry0987/config.svg?style=flat-square)](https://packagist.org/packages/carry0987/config)  
A library for easily manipulating config of php program

## Overview
PHP-Config is a versatile library designed to simplify the manipulation and management of configurations within PHP applications. Whether it's handling configurations stored in a database, processing configurations for runtime use, or integrating with caching systems like Redis, PHP-Config streamlines your workflow.

## Features
- Easy retrieval and updating of configuration settings.
- Support for fetching configurations from a database using PDO.
- Optional integration with Redis for caching configuration values.
- Customizable table name for configurations stored in the database.
- Robust error handling through custom exceptions.
- Type-safe operations with modern PHP type declarations.

## Requirements
- PHP 7.4 or higher
- PDO extension for database interaction
- Optional: Redis server and PHP extension for Redis, if caching is desired

## Installation
To integrate PHP-Config into your project, you can clone this repository and include it directly, or use Composer to manage the dependency.

To install using Composer:
```
composer require carry0987/config
```

## Usage

### Initialization
Below is an example of how to initialize the `Config` class with a PDO connection:
```php
use carry0987\Config\Config;
use carry0987\Redis\RedisTool;

// Assuming $pdo is an instance of PDO
$config = new Config($pdo);

// Optionally, you can set a custom table name for the configuration.
$config->setTableName('your_custom_config_table');
```

### Redis Integration (Optional)
If you have Redis set up and would like to utilize it for configuration caching:
```php
// Assuming $redis is an instance of RedisTool
$config->setRedis($redis);
```

### Adding a Configuration Setting
```php
// To add a new configuration setting:
$result = $config->addConfig('site_name', 'My Awesome Website');
```

### Retrieving a Configuration Setting
```php
// To fetch a configuration value by its key:
$siteName = $config->getConfig('site_name', true);
```

### Updating a Configuration Setting
```php
// To update an existing configuration setting:
$result = $config->updateConfig('site_name', 'My Even More Awesome Website');
```

## Contributing
Contributions to PHP-Config are welcome. Please feel free to fork the repository and submit pull requests.

## License
PHP-Config is released under the MIT License. See the bundled LICENSE file for details.
