<?php

declare(strict_types=1);

namespace Dbapi\Shared\Infrastructure\BlockEditor;

use InvalidArgumentException;

final class BlockEditorContractValidator
{
    private const ROW_LAYOUT_COLUMN_COUNT = [
        '1' => 1,
        '1-1' => 2,
        '1-2' => 2,
        '2-1' => 2,
        '1-1-1' => 3,
    ];

    private const ROW_WIDTHS = ['contained', 'full'];
    private const BLOCK_TYPES = ['rich_text', 'multimedia', 'form', 'iframe'];

    /**
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validate(mixed $pageContent): array
    {
        $errors = [];

        if (!is_array($pageContent)) {
            return [
                'valid' => false,
                'errors' => ['pageContent must be an array of rows'],
            ];
        }

        foreach ($pageContent as $rowIndex => $row) {
            $this->validateRow($row, 'pageContent[' . $rowIndex . ']', $errors);
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function assertValid(mixed $pageContent): void
    {
        $result = $this->validate($pageContent);

        if ($result['valid']) {
            return;
        }

        throw new InvalidArgumentException(implode(PHP_EOL, $result['errors']));
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateRow(mixed $row, string $path, array &$errors): void
    {
        if (!$this->isObjectArray($row)) {
            $errors[] = $path . ' must be an object';
            return;
        }

        $this->assertAllowedKeys($row, ['type', 'layout', 'width', 'bgColor', 'bgImageUrl', 'columns'], $path, $errors);

        if (($row['type'] ?? null) !== 'row') {
            $errors[] = $path . '.type must be "row"';
        }

        $layout = $row['layout'] ?? null;
        if (!is_string($layout) || !array_key_exists($layout, self::ROW_LAYOUT_COLUMN_COUNT)) {
            $errors[] = $path . '.layout must be one of: ' . implode(', ', array_keys(self::ROW_LAYOUT_COLUMN_COUNT));
        }

        $width = $row['width'] ?? null;
        if (!is_string($width) || !in_array($width, self::ROW_WIDTHS, true)) {
            $errors[] = $path . '.width must be one of: ' . implode(', ', self::ROW_WIDTHS);
        }

        if (array_key_exists('bgColor', $row) && !$this->isValidHexColor($row['bgColor'])) {
            $errors[] = $path . '.bgColor must be a valid hex color (#RRGGBB)';
        }

        if (array_key_exists('bgImageUrl', $row) && !$this->isValidUrl($row['bgImageUrl'])) {
            $errors[] = $path . '.bgImageUrl must be a valid URL';
        }

        $columns = $row['columns'] ?? null;
        if (!is_array($columns)) {
            $errors[] = $path . '.columns must be an array';
            return;
        }

        if (is_string($layout) && array_key_exists($layout, self::ROW_LAYOUT_COLUMN_COUNT)) {
            $expectedColumns = self::ROW_LAYOUT_COLUMN_COUNT[$layout];
            if (count($columns) !== $expectedColumns) {
                $errors[] = $path . '.columns must contain exactly ' . $expectedColumns . ' columns for layout "' . $layout . '"';
            }
        }

        foreach ($columns as $columnIndex => $column) {
            $this->validateColumn($column, $path . '.columns[' . $columnIndex . ']', $errors);
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateColumn(mixed $column, string $path, array &$errors): void
    {
        if (!$this->isObjectArray($column)) {
            $errors[] = $path . ' must be an object';
            return;
        }

        $this->assertAllowedKeys($column, ['blocks'], $path, $errors);

        if (!array_key_exists('blocks', $column) || !is_array($column['blocks'])) {
            $errors[] = $path . '.blocks must be an array';
            return;
        }

        foreach ($column['blocks'] as $blockIndex => $block) {
            $this->validateBlock($block, $path . '.blocks[' . $blockIndex . ']', $errors);
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function validateBlock(mixed $block, string $path, array &$errors): void
    {
        if (!$this->isObjectArray($block)) {
            $errors[] = $path . ' must be an object';
            return;
        }

        $this->assertAllowedKeys($block, ['type', 'data'], $path, $errors);

        $type = $block['type'] ?? null;
        if (!is_string($type) || !in_array($type, self::BLOCK_TYPES, true)) {
            $errors[] = $path . '.type must be one of: ' . implode(', ', self::BLOCK_TYPES);
            return;
        }

        $data = $block['data'] ?? null;
        if (!$this->isObjectArray($data)) {
            $errors[] = $path . '.data must be an object';
            return;
        }

        match ($type) {
            'rich_text' => $this->validateRichTextData($data, $path . '.data', $errors),
            'multimedia' => $this->validateMultimediaData($data, $path . '.data', $errors),
            'form' => $this->validateFormData($data, $path . '.data', $errors),
            'iframe' => $this->validateIframeData($data, $path . '.data', $errors),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $errors
     */
    private function validateRichTextData(array $data, string $path, array &$errors): void
    {
        $this->assertAllowedKeys($data, ['content', 'backgroundImageUrl'], $path, $errors);

        if (!array_key_exists('content', $data) || !is_string($data['content']) || trim($data['content']) === '') {
            $errors[] = $path . '.content must be a non-empty HTML string';
        }

        if (array_key_exists('backgroundImageUrl', $data) && !$this->isValidUrl($data['backgroundImageUrl'])) {
            $errors[] = $path . '.backgroundImageUrl must be a valid URL';
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $errors
     */
    private function validateMultimediaData(array $data, string $path, array &$errors): void
    {
        $this->assertAllowedKeys($data, ['url', 'type', 'caption'], $path, $errors);

        if (!array_key_exists('url', $data) || !$this->isValidUrl($data['url'])) {
            $errors[] = $path . '.url must be a valid URL';
        }

        if (!array_key_exists('type', $data) || !is_string($data['type']) || !in_array($data['type'], ['image', 'video'], true)) {
            $errors[] = $path . '.type must be one of: image, video';
        }

        if (array_key_exists('caption', $data) && !is_string($data['caption'])) {
            $errors[] = $path . '.caption must be a string';
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $errors
     */
    private function validateFormData(array $data, string $path, array &$errors): void
    {
        $this->assertAllowedKeys($data, ['form_id'], $path, $errors);

        if (!array_key_exists('form_id', $data) || !is_int($data['form_id']) || $data['form_id'] <= 0) {
            $errors[] = $path . '.form_id must be a positive integer';
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $errors
     */
    private function validateIframeData(array $data, string $path, array &$errors): void
    {
        $this->assertAllowedKeys($data, ['src', 'title', 'height', 'allowFullscreen'], $path, $errors);

        if (!array_key_exists('src', $data) || !$this->isValidUrl($data['src'])) {
            $errors[] = $path . '.src must be a valid URL';
        }

        if (!array_key_exists('title', $data) || !is_string($data['title']) || trim($data['title']) === '') {
            $errors[] = $path . '.title must be a non-empty string';
        }

        if (!array_key_exists('height', $data) || !is_int($data['height']) || $data['height'] <= 0) {
            $errors[] = $path . '.height must be a positive integer';
        }

        if (!array_key_exists('allowFullscreen', $data) || !is_bool($data['allowFullscreen'])) {
            $errors[] = $path . '.allowFullscreen must be a boolean';
        }
    }

    /**
     * @param array<string, mixed> $object
     * @param array<int, string> $allowedKeys
     * @param array<int, string> $errors
     */
    private function assertAllowedKeys(array $object, array $allowedKeys, string $path, array &$errors): void
    {
        foreach (array_keys($object) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                $errors[] = $path . '.' . $key . ' is not allowed by the contract';
            }
        }
    }

    private function isValidHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    private function isValidUrl(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function isObjectArray(mixed $value): bool
    {
        return is_array($value) && !array_is_list($value);
    }
}
