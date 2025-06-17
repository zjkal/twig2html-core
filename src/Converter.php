<?php

namespace zjkal\twig2html\core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Converter
{
    private Environment $twig;
    private array $options;

    /**
     * 初始化转换器
     *
     * @param array $options Twig环境选项
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'cache' => false,
            'debug' => false,
            'auto_reload' => true,
            'strict_variables' => false,
        ], $options);

        $this->twig = new Environment(
            new FilesystemLoader([]),
            $this->options
        );
    }

    /**
     * 转换单个Twig模板文件为HTML
     *
     * @param string $templatePath Twig模板文件路径
     * @param string $outputPath 输出HTML文件路径
     * @param array $variables 模板变量
     * @return bool
     */
    public function convert(string $templatePath, string $outputPath, array $variables = []): bool
    {
        try {
            if (!file_exists($templatePath)) {
                throw new \RuntimeException("模板文件不存在：{$templatePath}");
            }
            // 获取模板目录和文件名
            $templateDir = dirname($templatePath);
            $templateName = basename($templatePath);

            // 重新设置模板加载器
            $this->twig->setLoader(new FilesystemLoader([$templateDir]));

            // 渲染模板
            $html = $this->twig->render($templateName, $variables);

            // 确保输出目录存在
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // 写入文件
            return file_put_contents($outputPath, $html) !== false;
        } catch (\Exception $e) {
            // 记录错误日志或抛出异常
            throw $e;
        }
    }

    /**
     * 批量转换目录下的所有Twig模板
     *
     * @param string $sourceDir 源目录
     * @param string $outputDir 输出目录
     * @param array $variables 全局变量
     * @return array 转换结果，包含成功和失败的文件列表
     */
    public function convertDirectory(string $sourceDir, string $outputDir, array $variables = []): array
    {
        if (!is_dir($sourceDir)) {
            throw new \RuntimeException("源目录不存在：{$sourceDir}");
        }

        $result = [
            'success' => [],
            'failed' => []
        ];

        // 确保目录路径以斜杠结尾
        $sourceDir = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR;
        $outputDir = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR;

        // 递归获取所有.twig文件
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'twig') {
                continue;
            }

            $sourcePath = $file->getRealPath();
            $relativePath = substr($sourcePath, strlen($sourceDir));
            $outputPath = $outputDir . substr($relativePath, 0, -5) . '.html';

            try {
                if ($this->convert($sourcePath, $outputPath, $variables)) {
                    $result['success'][] = $relativePath;
                } else {
                    $result['failed'][] = $relativePath;
                }
            } catch (\Exception $e) {
                $result['failed'][] = $relativePath;
            }
        }

        return $result;
    }
}
