<?php

use App\Http\Resources\Configurations\Destination;
use App\Service\Implements\TransformServiceImpl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Mustache\Engine;
use Symfony\Component\HttpFoundation\Request as RequestAlias;
uses(RefreshDatabase::class);
it('builds payloads correctly from extracted data', function () {
    $sourceBody = [
        'data' => [
            'orderId' => 'ORD-999',
            'customer' => ['name' => 'Tirta'],
            'total' => 125000,
        ]
    ];

    $destination = new Destination(
        url: 'https://fake.endpoint',
        method: RequestAlias::METHOD_POST,
        headers: ['Content-Type' => 'application/json'],
        extract: [
            'order_id' => '$.data.orderId',
            'buyer'    => '$.data.customer.name',
            'total'    => '$.data.total',
        ],
        foreach: null,
        body_template: 'Order {{order_id}} by {{buyer}}: total {{total}}'
    );

    $service = new TransformServiceImpl(new Engine(), app()->make(Logger::class));
    $payloads = $service->buildPayloads($sourceBody, $destination);

    expect($payloads)->toHaveCount(1);
    expect($payloads[0]['body'])->toBe('Order ORD-999 by Tirta: total 125000');
    expect($payloads[0]['headers'])->toBe(['Content-Type' => 'application/json']);
});

it('builds multiple payloads using foreach', function () {
    $sourceBody = [
        'data' => [
            'orderId' => 'ORD-123',
            'customer' => ['name' => 'Andi'],
            'items' => [
                ['name' => 'Kaos', 'qty' => 2, 'cheap' => true],
                ['name' => 'Celana', 'qty' => 1, 'cheap' => false],
            ],
        ]
    ];

    $destination = new Destination(
        url: 'https://fake.endpoint',
        method: RequestAlias::METHOD_POST,
        headers: ['Content-Type' => 'application/json'],
        extract: [
            'item_name' => '@.name',
            'item_qty'  => '@.qty',
            'buyer'     => '$.data.customer.name',
            'isCheap'   => '@.cheap',
        ],
        foreach: '$.data.items[*]',
        body_template: 'Buyer {{buyer}} ordered {{item_qty}} x {{item_name}}'
    );
    $logger = app()->make(Logger::class);
    $service = new TransformServiceImpl(new Engine(), $logger);
    $payloads = $service->buildPayloads($sourceBody, $destination);

    expect($payloads)->toHaveCount(2)
        ->and($payloads[0]['body'])->toBe('Buyer Andi ordered 2 x Kaos')
        ->and($payloads[1]['body'])->toBe('Buyer Andi ordered 1 x Celana');
});
