<?PHP

namespace ArangoDB\common;

use Knight\Configuration as KnightConfiguration;

use Knight\armor\CustomException;

class Configuration
{
    use KnightConfiguration;

    const CONFIGURATION_FILENAME = 'ArangoDB';
    const CONFIGURATION_CONSTANT = 'PARAMETERS';

    public static function get() : array
    {
        $configuration = static::getConstant(static::CONFIGURATION_FILENAME, static::CONFIGURATION_CONSTANT);
        if (null == $configuration) throw new CustomException('developer/arangodb/connection/configuration/parameters');
        return $configuration;
    }
}