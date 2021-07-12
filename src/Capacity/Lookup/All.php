<?php declare(strict_types=1);

namespace Kiboko\Plugin\Akeneo\Capacity\Lookup;

use Kiboko\Plugin\Akeneo;
use PhpParser\Builder;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValue;

final class All implements Akeneo\Capacity\CapacityInterface
{
    private static $endpoints = [
        // Core Endpoints
        'product',
        'category',
        'attribute',
        'attributeOption',
        'attributeGroup',
        'family',
        'productMediaFile',
        'locale',
        'channel',
        'currency',
        'measureFamily',
        'associationType',
        'familyVariant',
        'productModel',
        // Enterprise Endpoints
        'publishedProduct',
        'productModelDraft',
        'productDraft',
        'asset',
        'assetCategory',
        'assetTag',
        'referenceEntityRecord',
        'referenceEntityAttribute',
        'referenceEntityAttributeOption',
        'referenceEntity',
    ];

    public function __construct(private ExpressionLanguage $interpreter)
    {
    }

    public function applies(array $config): bool
    {
        return isset($config['type'])
            && in_array($config['type'], self::$endpoints)
            && isset($config['method'])
            && $config['method'] === 'all';
    }

    private function compileFilters(array ...$filters): Node\Expr
    {
        $builder = new Akeneo\Builder\Search();
        foreach ($filters as $filter) {
            $builder->addFilter(
                field: compileValue($this->interpreter, $filter["field"]),
                operator: compileValue($this->interpreter, $filter["operator"]),
                value: compileValue($this->interpreter, $filter["value"]),
                scope: array_key_exists('scope', $filter) ? compileValue($this->interpreter, $filter["scope"]) : null,
                locale: array_key_exists('locale', $filter) ? compileValue($this->interpreter, $filter["locale"]) : null
            );
        }

        return $builder->getNode();
    }

    public function getBuilder(array $config): Builder
    {
        $builder = (new Akeneo\Builder\Capacity\Lookup\All())
            ->withEndpoint(new Node\Identifier(sprintf('get%sApi', ucfirst($config['type']))));

        if (isset($config['search']) && is_array($config['search'])) {
            $builder->withSearch($this->compileFilters(...$config['search']));
        }

        if (in_array($config['type'], ['attributeOption','familyVariant'])
            && array_key_exists('code', $config)
        ) {
            $builder->withParameter(compileValue($this->interpreter, $config['code']));
            $builder->withParameterName($this->getParameterNameByConfig($config['type']));
        }

        return $builder;
    }

    private function getParameterNameByConfig(string $type): string
    {
        $mapping = [
            'familyVariant' => 'familyCode',
            'attributeOption' => 'attributeCode',
            'referenceEntity' => 'referenceEntityCode',
            'referenceEntityAttribute' => 'referenceEntityCode',
            'referenceEntityRecord' => 'referenceEntityCode'
        ];

        return $mapping[$type];
    }
}
