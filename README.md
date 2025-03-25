# Templating Client

A PHP wrapper around Twig with extended functionality for template formatting.

## Installation

With SSH authentication:
Add the private repository to your composer.json:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:vlizBE/templating-client.git"
        }
    ]
}
```
Require the package:
```bash
composer require vliz/templating-client:dev-master
```

## Usage

```php
use Vliz\TemplatingClient\TemplatingClient;

new TemplatingClient($pathToYourTemplatesFolder);
echo $this->templatingClient->render($templateFileName, $dataObject);
```
