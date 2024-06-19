<?php

/**
 * Ultimate Unit Test Generator (Uutg)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @author    Marius Strajeru
 */

declare(strict_types=1);

namespace Umc\Uutg;

class Config
{
    public const DEFAULT_TEMPLATE = __DIR__ . '../test.phtml';
    public const COVERAGE_MODE_ANNOTATION = 1;
    public const COVERAGE_MODE_DOC_BLOCK = 2;
    public function __construct(private array $config)
    {
    }

    public function getHeader(): string
    {
        $header = $this->config['header'] ?? [];
        if (is_array($header)) {
            return implode(PHP_EOL, $header);
        }
        if (is_string($header)) {
            return $header;
        }
        if (is_callable($header)) {
            return $header();
        }
        return '';
    }

    public function getTemplate(): string
    {
        return $this->config['template'] ?? self::DEFAULT_TEMPLATE;
    }

    public function getTestCaseClassName(): string
    {
        return $this->config['test_case_class_name'] ?? '\\PHPUnit\\Framework\\TestCase';
    }

    public function getNonMockable(): array
    {
        return $this->config['non_mockable'] ?? [];
    }

    public function getReplace(): array
    {
        return $this->config['replace'] ?? [];
    }

    public function getNonTestableMethods(): array
    {
        return $this->config['non_testable'] ?? [];
    }

    public function getNamespaceStrategy(): array|callable
    {
        return $this->config['namespace_strategy'] ?? [];
    }

    public function isStrongType(): bool
    {
        return (bool)($this->config['strong_type'] ?? true);
    }

    public function getMockObjectClass(): string
    {
        return $this->config['mock_object_class'] ?? '\\PHPUnit\\Framework\\MockObject\\MockObject';
    }

    public function getMemberAccess(): string
    {
        return $this->config['member_access'] ?? 'private';
    }

    public function isMemberDocBlocks(): bool
    {
        return (bool)($this->config['member_doc_blocks'] ?? true);
    }

    public function isUnionType(): bool
    {
        return (bool)($this->config['union_type'] ?? true);
    }

    public function getTestMethodPrefix(): string
    {
        return $this->config['test_method_prefix'] ?? 'test';
    }

    public function isTestAttribute(): bool
    {
        return (bool)($this->config['use_test_attribute'] ?? true);
    }

    public function getTestAttributeClass()
    {
        return $this->config['test_attribute_class'] ?? '\\PHPUnit\\Framework\\Attributes\\Test';
    }

    public function getCoverageMode(): int
    {
        return (int)($this->config['coverage_mode'] ?? self::COVERAGE_MODE_ANNOTATION);
    }

    public function getCoversClassAnnotation(): string
    {
        return $this->config['covers_class_annotation'] ?? '\\PHPUnit\\Framework\\Attributes\\CoversClass';
    }

    public function useFQN(): bool
    {
        return (bool)($this->config['use_fqn'] ?? false);
    }
}
