# Twig2Html Core

这是一个用于将Twig模板转换为静态HTML文件的PHP核心库。它提供了简单而强大的API，可以单独转换文件或批量转换整个目录。

## 安装

使用Composer安装：

```bash
composer require zjkal/twig2html-core
```

## 使用方法

### 单文件转换

```php
use zjkal\twig2html\core\Converter;

$converter = new Converter();
$converter->convert(
    'path/to/template.twig',
    'path/to/output.html',
    ['name' => 'World']
);
```

### 目录批量转换

```php
use zjkal\twig2html\core\Converter;

$converter = new Converter();
$result = $converter->convertDirectory(
    'path/to/templates',
    'path/to/output',
    ['name' => 'World']
);

// 查看转换结果
print_r($result['success']); // 成功转换的文件列表
print_r($result['failed']); // 转换失败的文件列表
```

### 自定义Twig环境选项

```php
use zjkal\twig2html\core\Converter;

$converter = new Converter([
    'cache' => '/path/to/cache',
    'debug' => true,
    'auto_reload' => true,
    'strict_variables' => true
]);
```

## 测试

运行单元测试：

```bash
vendor/bin/phpunit
```

## 许可证

MIT License