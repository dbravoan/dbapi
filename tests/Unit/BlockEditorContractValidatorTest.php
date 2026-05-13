<?php

declare(strict_types=1);

namespace Tests\Unit;

use Dbapi\Shared\Infrastructure\BlockEditor\BlockEditorContractValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlockEditorContractValidatorTest extends TestCase
{
    #[Test]
    public function it_accepts_a_valid_page_content_payload(): void
    {
        $validator = new BlockEditorContractValidator();

        $payload = [
            [
                'type' => 'row',
                'layout' => '1-1',
                'width' => 'full',
                'bgColor' => '#0f172a',
                'bgImageUrl' => 'https://cdn.example.com/hero-bg.jpg',
                'columns' => [
                    [
                        'blocks' => [
                            [
                                'type' => 'rich_text',
                                'data' => [
                                    'content' => '<h1>Innovacion Medica</h1><p>Descubre el nuevo Zionic Pro Max.</p>',
                                ],
                            ],
                        ],
                    ],
                    [
                        'blocks' => [
                            [
                                'type' => 'form',
                                'data' => ['form_id' => 1],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'row',
                'layout' => '1',
                'width' => 'contained',
                'columns' => [
                    [
                        'blocks' => [
                            [
                                'type' => 'iframe',
                                'data' => [
                                    'src' => 'https://www.youtube.com/embed/abc123',
                                    'title' => 'Demo video',
                                    'height' => 420,
                                    'allowFullscreen' => true,
                                ],
                            ],
                            [
                                'type' => 'multimedia',
                                'data' => [
                                    'url' => 'https://cdn.example.com/asset.jpg',
                                    'type' => 'image',
                                    'caption' => 'Main visual',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $validator->validate($payload);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    #[Test]
    public function it_rejects_a_row_with_wrong_column_count_for_layout(): void
    {
        $validator = new BlockEditorContractValidator();

        $payload = [
            [
                'type' => 'row',
                'layout' => '1-1-1',
                'width' => 'contained',
                'columns' => [
                    ['blocks' => []],
                    ['blocks' => []],
                ],
            ],
        ];

        $result = $validator->validate($payload);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('must contain exactly 3 columns', implode(' | ', $result['errors']));
    }

    #[Test]
    public function it_rejects_invalid_block_payloads(): void
    {
        $validator = new BlockEditorContractValidator();

        $payload = [
            [
                'type' => 'row',
                'layout' => '1',
                'width' => 'contained',
                'columns' => [
                    [
                        'blocks' => [
                            [
                                'type' => 'rich_text',
                                'data' => [
                                    'content' => '',
                                ],
                            ],
                            [
                                'type' => 'iframe',
                                'data' => [
                                    'src' => 'invalid-url',
                                    'title' => '',
                                    'height' => -100,
                                    'allowFullscreen' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $validator->validate($payload);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(4, count($result['errors']));
    }

    #[Test]
    public function assert_valid_throws_exception_when_payload_is_invalid(): void
    {
        $validator = new BlockEditorContractValidator();

        $payload = [
            [
                'type' => 'row',
                'layout' => '1',
                'width' => 'contained',
                'columns' => [
                    [
                        'blocks' => [
                            [
                                'type' => 'form',
                                'data' => [
                                    'form_id' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $validator->assertValid($payload);
    }
}
