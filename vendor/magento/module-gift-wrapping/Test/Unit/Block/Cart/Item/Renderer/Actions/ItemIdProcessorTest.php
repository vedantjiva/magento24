<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Block\Cart\Item\Renderer\Actions;

use Magento\GiftWrapping\Block\Cart\Item\Renderer\Actions\ItemIdProcessor;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemIdProcessorTest extends TestCase
{
    /** @var ItemIdProcessor */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new ItemIdProcessor();
    }

    /**
     * @param int $itemId
     * @param array $jsLayout
     * @param array $result
     * @dataProvider dataProviderProcess
     */
    public function testProcess($itemId, array $jsLayout, array $result)
    {
        /**
         * @var Item|MockObject $itemMock
         */
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->any())
            ->method('getId')
            ->willReturn($itemId);

        $this->assertEquals($result, $this->model->process($jsLayout, $itemMock));
    }

    public function dataProviderProcess()
    {
        return [
            [
                12,
                ['components' => []],
                ['components' => []],
            ],
            [
                21,
                ['components' => ['giftOptionsCartItem' => ['config' => ['key' => 'value']]]],
                ['components' => ['giftOptionsCartItem' => ['config' => ['key' => 'value']]]],
            ],
            [
                22,
                [
                    'components' => [
                        'giftOptionsCartItem-22' => ['children' => ['giftWrapping' => []]]
                    ]
                ],
                [
                    'components' => [
                        'giftOptionsCartItem-22' => ['children' => ['giftWrapping' => ['config' => ['itemId' => 22]]]]
                    ],
                ],
            ],
            [
                23,
                [
                    'components' => [
                        'giftOptionsCartItem-23' => ['children' => ['giftWrapping' => ['config' => ['key' => 'value']]]]
                    ]
                ],
                [
                    'components' => [
                        'giftOptionsCartItem-23' => [
                            'children' => [
                                'giftWrapping' => [
                                    'config' => [
                                        'key' => 'value',
                                        'itemId' => 23
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            [
                24,
                [
                    'components' => [
                        'giftOptionsCartItem-24' => [
                            'children' => [
                                'giftWrapping' => [
                                    'config' => ['key' => 'value'],
                                    'key2' => 'value2'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'components' => [
                        'giftOptionsCartItem-24' => [
                            'children' => [
                                'giftWrapping' => ['config' => ['key' => 'value', 'itemId' => 24], 'key2' => 'value2']
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }
}
