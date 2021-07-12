<?php declare(strict_types=1);

namespace Kiboko\Plugin\Akeneo\Capacity\Lookup;

use Kiboko\Contract\Configurator;
use Kiboko\Plugin\Akeneo;
use PhpParser\Builder;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValue;

final class Download implements Akeneo\Capacity\CapacityInterface
{
    private static $endpoints = [
        // Core Endpoints
        'productMediaFile',
        // Enterprise Endpoints
        'asset',
    ];

    public function __construct(private ExpressionLanguage $interpreter)
    {
    }

    public function applies(array $config): bool
    {
        return isset($config['type'])
            && in_array($config['type'], self::$endpoints)
            && isset($config['method'])
            && $config['method'] === 'download';
    }
    
    public function getBuilder(array $config): Builder
    {
        $builder = (new Akeneo\Builder\Capacity\Lookup\Download())
            ->withEndpoint(new Node\Identifier(sprintf('get%sApi', ucfirst($config['type']))));

        if (!array_key_exists('file', $config)) {
            throw new Configurator\InvalidConfigurationException(
                'The configuration option "file" should be defined.'
            );
        }
        $builder->withParameter(compileValue($this->interpreter, $config['file']));

        return $builder;
    }
}
