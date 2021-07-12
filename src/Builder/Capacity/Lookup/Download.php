<?php declare(strict_types=1);

namespace Kiboko\Plugin\Akeneo\Builder\Capacity\Lookup;


use Kiboko\Component\SatelliteToolbox\Builder\IsolatedValueAppendingBuilder;
use Kiboko\Plugin\Akeneo\MissingEndpointException;
use PhpParser\Builder;
use PhpParser\Node;

final class Download implements Builder
{
    private null|Node\Expr|Node\Identifier $endpoint;
    private null|Node\Expr $parameter;

    public function __construct()
    {
        $this->endpoint = null;
        $this->parameter = null;
    }

    public function withEndpoint(Node\Expr|Node\Identifier $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function withParameter(Node\Expr $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function getNode(): Node
    {
        if ($this->endpoint === null) {
            throw new MissingEndpointException(
                message: 'Please check your capacity builder, you should have selected an endpoint.'
            );
        }

        return (new IsolatedValueAppendingBuilder(
            new Node\Expr\Variable('input'),
            new Node\Expr\Variable('lookup'),
            [
                new Node\Stmt\If_(
                    cond: new Node\Expr\FuncCall(
                        name: new Node\Name('is_null'),
                        args: array_filter(
                            [
                                $this->parameter !== null ? new Node\Arg(
                                    value: $this->parameter,
                                ) : null
                            ],
                        ),
                    ),
                    subNodes: [
                        'stmts' => [
                            new Node\Stmt\Return_(
                                expr: new Node\Expr\ConstFetch(
                                    name: new Node\Name(name: 'null'),
                                ),
                            )
                        ],
                    ],
                ),
                new Node\Stmt\TryCatch(
                    stmts: [
                        new Node\Stmt\Expression(
                            expr: new Node\Expr\Assign(
                                var: new Node\Expr\Variable('image'),
                                expr: new Node\Expr\MethodCall(
                                    var: new Node\Expr\MethodCall(
                                        var: new Node\Expr\MethodCall(
                                            var: new Node\Expr\MethodCall(
                                                var: new Node\Expr\PropertyFetch(
                                                    var: new Node\Expr\Variable('this'),
                                                    name: new Node\Identifier('client'),
                                                ),
                                                name: $this->endpoint
                                            ),
                                            name: new Node\Identifier('download'),
                                            args: array_filter(
                                                [
                                                    $this->parameter !== null ? new Node\Arg(
                                                        value: $this->parameter,
                                                        name: new Node\Identifier('code'),
                                                    ) : null
                                                ],
                                            ),
                                        ),
                                        name: new Node\Identifier('getBody'),
                                    ),
                                    name: new Node\Identifier('getContents'),
                                ),
                            ),
                        )
                    ],
                    catches: [
                        new Node\Stmt\Catch_(
                            types: [
                                new Node\Name\FullyQualified('Akeneo\Pim\ApiClient\Exception\HttpException'),
                            ],
                            var: new Node\Expr\Variable('exception'),
                            stmts: [
                                new Node\Stmt\Expression(
                                    expr: new Node\Expr\MethodCall(
                                        var: new Node\Expr\PropertyFetch(
                                            var: new Node\Expr\Variable('this'),
                                            name: 'logger',
                                        ),
                                        name: new Node\Identifier('error'),
                                        args: [
                                            new Node\Arg(
                                                value: new Node\Expr\MethodCall(
                                                    var: new Node\Expr\Variable('exception'),
                                                    name: new Node\Identifier('getMessage'),
                                                ),
                                            ),
                                            new Node\Arg(
                                                value: new Node\Expr\Array_(
                                                    items: [
                                                        new Node\Expr\ArrayItem(
                                                            value: new Node\Expr\Variable('exception'),
                                                            key: new Node\Scalar\String_('exception'),
                                                        ),
                                                    ],
                                                    attributes: [
                                                        'kind' => Node\Expr\Array_::KIND_SHORT,
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                                new Node\Stmt\Expression(
                                    expr: new Node\Expr\MethodCall(
                                        var: new Node\Expr\Variable('bucket'),
                                        name: new Node\Identifier('reject'),
                                        args: [
                                            new Node\Arg(
                                                new Node\Expr\Variable('input'),
                                            )
                                        ],
                                   )
                                )
                            ],
                        ),
                    ],
                ),
                new Node\Stmt\Return_(
                    expr: new Node\Expr\Variable('image'),
                ),
            ],
            new Node\Expr\Variable('bucket')
        ))->getNode();
    }
}
