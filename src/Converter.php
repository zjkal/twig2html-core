<?php

namespace zjkal\twig2html\core;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
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
            'cache'            => false,
            'debug'            => false,
            'auto_reload'      => true,
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
     * @param string $outputPath   输出HTML文件路径
     * @param array  $variables    模板变量
     * @return bool
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function convert(string $templatePath, string $outputPath, array $variables = []): bool
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("模板文件不存在：{$templatePath}");
        }

        // 检查是否为部分模板
        if ($this->isPartialTemplate(basename($templatePath))) {
            throw new \RuntimeException("不支持转换部分模板：{$templatePath}");
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
    }

    private function isPartialTemplate(string $filename): bool
    {
        $suffix = '.part.twig';
        return substr($filename, -strlen($suffix)) === $suffix;
    }

    /**
     * 批量转换目录下的所有Twig模板
     *
     * @param string      $sourceDir       源目录
     * @param string      $outputDir       输出目录
     * @param string|null $dataDir         数据目录，存放与模板同名的PHP数据文件
     * @param array       $globalVariables 全局变量
     * @return array 转换结果，包含成功和失败的文件列表
     */
    public function convertDirectory(string $sourceDir, string $outputDir, ?string $dataDir = null, array $globalVariables = []): array
    {
        if (!is_dir($sourceDir)) {
            throw new \RuntimeException("源目录不存在：{$sourceDir}");
        }

        $result = [
            'success' => [],
            'failed' => [],
            'skipped' => [] // 添加跳过的文件列表
        ];

        // 确保目录路径以斜杠结尾
        $sourceDir = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR;
        $outputDir = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR;
        if ($dataDir) {
            $dataDir = rtrim($dataDir, '/\\') . DIRECTORY_SEPARATOR;
        }

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

            // 检查是否为部分模板
            if ($this->isPartialTemplate($file->getFilename())) {
                $result['skipped'][] = '📝' . basename($sourceDir) . '/' . $relativePath;
                continue;
            }

            $outputPath = $outputDir . substr($relativePath, 0, -5) . '.html';

            // 获取模板对应的数据文件
            $variables = $globalVariables;
            $dataFilePath = null;
            if ($dataDir && is_dir($dataDir)) {
                $dataFile = $dataDir . substr($relativePath, 0, -5) . '.php';
                if (file_exists($dataFile)) {
                    $templateData = require $dataFile;
                    if (is_array($templateData)) {
                        $variables = array_merge($variables, $templateData);
                        $dataFilePath = substr($dataFile, strlen($dataDir));
                    }
                }
            }

            try {
                if ($this->convert($sourcePath, $outputPath, $variables)) {
                    // 格式化成功信息
                    $successInfo = '📝' . basename($sourceDir) . '/' . $relativePath;
                    if ($dataFilePath) {
                        $successInfo .= ' + 📊' . basename($dataDir) . '/' . $dataFilePath;
                    }
                    $successInfo .= ' => 📄' . basename($outputDir) . '/' . substr($outputPath, strlen($outputDir));
                    $result['success'][] = $successInfo;
                } else {
                    $result['failed'][] = '📝' . basename($sourceDir) . '/' . $relativePath;
                }
            } catch (\Exception $e) {
                $result['failed'][] = '📝' . basename($sourceDir) . '/' . $relativePath;
            }
        }

        return $result;
    }
}
