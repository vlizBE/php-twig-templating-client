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
Twig filter and function examples
```php
//function baseref returns a base url, set by .env BASE_REF
//function uritexpand expands a uri with a given fragment using https://github.com/rize/UriTemplate
@base <{{baseref()}}{{uritexpand("feed/event?fragment={page}", _)}}> .

//filter xsd returns a properly formatted ttl value for types 'boolean', 'integer', 'double', 'date',
//'datetime', 'anyuri', 'string', 'gyear', 'year', 'yyyy', 'gyearmonth', 'year-month', 'yyyy-mm',
//'auto-date', 'auto-number', 'auto-any', 'auto'
dct:title {{ _.refrec.StandardTitle | xsd("xsd:string") }} ;

//filter uri escapes, trims and returns a uri
schema:url {{url.URL | trim | uri }} ;

//filter fixUrl adds protocol to url
<dd><a href="{{url.URL | fixUrl}}">{{url.URL}}</a></dd>
```

See tests/demo.twig for more examples