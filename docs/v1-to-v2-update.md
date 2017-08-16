## Updating from v1 to v2

The following changes were made to the Application package between v1 and v2.

### PHP 5 support dropped

The Application package now requires PHP 7.0 or newer.

### PSR-7 Responses now supported
In order to support PSR-7 responses there is a single break in backwards incompatibility. The `\Joomla\Application\AbstractWebApplication::getBody()` method does not have a `toBody` parameter.

The package internals use the Zend Framework [Diactoros package](https://github.com/zendframework/zend-diactoros) for building the Response object. If you wish to use another PSR-7 compatible library you will need to extend the `setHeader`, `getHeaders`, `clearHeaders`, `setBody`, `prependBody`, and `appendBody` methods.

### AbstractWebApplication::getFormToken is now an abstract method
The method `\Joomla\Application\AbstractWebApplication::getFormToken` has been made an
abstract method. Application's should specify their own logic here to generate a form
token.

### ColorStyle method typing

The method parameters for the `\Joomla\Application\Cli\ColorStyle` are now all typehinted (including scalar types) and return type declarations added.  As this is a final class, there is no B/C concern for subclasses.