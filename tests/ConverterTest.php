<?php

namespace zjkal\twig2html\core\tests;

use PHPUnit\Framework\TestCase;
use zjkal\twig2html\core\Converter;

class ConverterTest extends TestCase
{
    private string $tempDir;
    private Converter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/twig2html-test-' . uniqid();
        mkdir($this->tempDir);
        $this->converter = new Converter();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }

    public function testConvertSingleFile(): void
    {
        // 创建测试模板
        $templateContent = 'Hello {{ name }}!';
        $templateFile = $this->tempDir . '/test.twig';
        file_put_contents($templateFile, $templateContent);

        // 设置输出文件
        $outputFile = $this->tempDir . '/test.html';

        // 执行转换
        $result = $this->converter->convert($templateFile, $outputFile, ['name' => 'World']);

        // 验证结果
        $this->assertTrue($result);
        $this->assertFileExists($outputFile);
        $this->assertEquals('Hello World!', file_get_contents($outputFile));
    }

    public function testConvertDirectory(): void
    {
        // 创建测试目录结构
        $sourceDir = $this->tempDir . '/source';
        $outputDir = $this->tempDir . '/output';
        mkdir($sourceDir);
        mkdir($sourceDir . '/subdir');

        // 创建测试模板
        file_put_contents($sourceDir . '/test1.twig', 'Hello {{ name }}!');
        file_put_contents($sourceDir . '/subdir/test2.twig', 'Goodbye {{ name }}!');
        file_put_contents($sourceDir . '/not-a-template.txt', 'This is not a template');

        // 执行转换
        $result = $this->converter->convertDirectory($sourceDir, $outputDir, ['name' => 'World']);

        // 验证结果
        $this->assertCount(2, $result['success']);
        $this->assertCount(0, $result['failed']);
        $this->assertFileExists($outputDir . '/test1.html');
        $this->assertFileExists($outputDir . '/subdir/test2.html');
        $this->assertEquals('Hello World!', file_get_contents($outputDir . '/test1.html'));
        $this->assertEquals('Goodbye World!', file_get_contents($outputDir . '/subdir/test2.html'));
    }

    public function testConvertWithInvalidTemplate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->converter->convert('non-existent.twig', 'output.html');
    }

    public function testConvertDirectoryWithInvalidSource(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->converter->convertDirectory('non-existent-dir', 'output-dir');
    }
}