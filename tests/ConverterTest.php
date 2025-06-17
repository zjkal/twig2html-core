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
        file_put_contents($sourceDir . '/header.part.twig', 'Header {{ name }}');
        file_put_contents($sourceDir . '/footer.part.twig', 'Footer {{ name }}');
        file_put_contents($sourceDir . '/not-a-template.txt', 'This is not a template');

        // 执行转换
        $result = $this->converter->convertDirectory($sourceDir, $outputDir, null, ['name' => 'World']);

        // 验证结果
        $this->assertCount(2, $result['success']);
        $this->assertCount(0, $result['failed']);
        $this->assertCount(2, $result['skipped']); // 验证跳过的部分模板数量
        $this->assertFileExists($outputDir . '/test1.html');
        $this->assertFileExists($outputDir . '/subdir/test2.html');
        $this->assertFileDoesNotExist($outputDir . '/header.html'); // 验证部分模板未被转换
        $this->assertFileDoesNotExist($outputDir . '/footer.html'); // 验证部分模板未被转换
        $this->assertEquals('Hello World!', file_get_contents($outputDir . '/test1.html'));
        $this->assertEquals('Goodbye World!', file_get_contents($outputDir . '/subdir/test2.html'));

        // 验证跳过的文件列表包含正确的文件
        $this->assertContains('header.part.twig', $result['skipped']);
        $this->assertContains('footer.part.twig', $result['skipped']);
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

    public function testConvertPartialTemplate(): void
    {
        // 创建部分模板文件
        $templateContent = 'Header {{ name }}';
        $templateFile = $this->tempDir . '/header.part.twig';
        file_put_contents($templateFile, $templateContent);

        // 设置输出文件
        $outputFile = $this->tempDir . '/header.html';

        // 期望转换部分模板时抛出异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('不支持转换部分模板：' . $templateFile);

        $this->converter->convert($templateFile, $outputFile, ['name' => 'World']);
    }

    public function testConvertDirectoryWithDataFiles(): void
    {
        // 创建测试目录结构
        $sourceDir = $this->tempDir . '/source';
        $outputDir = $this->tempDir . '/output';
        $dataDir = $this->tempDir . '/data';
        mkdir($sourceDir);
        mkdir($sourceDir . '/subdir');
        mkdir($dataDir);
        mkdir($dataDir . '/subdir');

        // 创建测试模板
        file_put_contents($sourceDir . '/test1.twig', 'Hello {{ name }}! {{ message }}');
        file_put_contents($sourceDir . '/subdir/test2.twig', 'Goodbye {{ name }}! {{ message }}');

        // 创建对应的数据文件
        file_put_contents($dataDir . '/test1.php', '<?php return ["message" => "Welcome"];');
        file_put_contents($dataDir . '/subdir/test2.php', '<?php return ["message" => "See you"];');

        // 执行转换，设置全局变量和使用数据文件
        $result = $this->converter->convertDirectory(
            $sourceDir,
            $outputDir,
            $dataDir,
            ['name' => 'World']
        );

        // 验证结果
        $this->assertCount(2, $result['success']);
        $this->assertCount(0, $result['failed']);
        $this->assertFileExists($outputDir . '/test1.html');
        $this->assertFileExists($outputDir . '/subdir/test2.html');
        $this->assertEquals('Hello World! Welcome', file_get_contents($outputDir . '/test1.html'));
        $this->assertEquals('Goodbye World! See you', file_get_contents($outputDir . '/subdir/test2.html'));

        // 测试使用不存在的数据目录
        $result = $this->converter->convertDirectory(
            $sourceDir,
            $outputDir,
            'non-existent-data-dir',
            ['name' => 'World', 'message' => 'Default']
        );

        // 验证结果 - 应该使用全局变量
        $this->assertCount(2, $result['success']);
        $this->assertCount(0, $result['failed']);
        $this->assertEquals('Hello World! Default', file_get_contents($outputDir . '/test1.html'));
        $this->assertEquals('Goodbye World! Default', file_get_contents($outputDir . '/subdir/test2.html'));

        // 删除数据文件
        unlink($dataDir . '/test1.php');
        unlink($dataDir . '/subdir/test2.php');

        // 测试数据文件不存在的情况
        $result = $this->converter->convertDirectory(
            $sourceDir,
            $outputDir,
            $dataDir,
            ['name' => 'World', 'message' => 'Fallback']
        );

        // 验证结果 - 应该使用全局变量
        $this->assertCount(2, $result['success']);
        $this->assertCount(0, $result['failed']);
        $this->assertEquals('Hello World! Fallback', file_get_contents($outputDir . '/test1.html'));
        $this->assertEquals('Goodbye World! Fallback', file_get_contents($outputDir . '/subdir/test2.html'));
    }
}