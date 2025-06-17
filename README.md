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
$converter = new \Zjkal\Twig2HtmlCore\Converter();
$result = $converter->convertDirectory(
    'templates',     // Twig模板目录
    'output',        // 输出目录
    'data',          // 数据目录（可选），存放与模板同名的PHP数据文件，目录不存在时使用全局变量
    ['name' => 'World'] // 全局变量（可选）
);

// 转换结果
var_dump($result['success']); // 成功转换的文件列表
var_dump($result['failed']);  // 转换失败的文件列表
var_dump($result['skipped']); // 跳过的部分模板文件列表
```

#### 数据文件

数据文件是与Twig模板同名的PHP文件，用于为模板提供变量数据。当数据目录或数据文件不存在时，将仅使用全局变量进行渲染。

目录结构示例：

```plaintext
templates/
  ├── page.twig          # 模板文件
  └── subdir/
      └── about.twig     # 子目录中的模板文件

data/
  ├── page.php          # 对应page.twig的数据文件
  └── subdir/
      └── about.php     # 对应about.twig的数据文件
```

数据文件的内容示例：

```php
<?php
return [
    'title' => '页面标题',
    'content' => '页面内容',
    // ... 其他变量
];
```

数据文件必须返回一个数组，其中的变量将与全局变量合并后传递给对应的模板。如果同一个变量同时存在于数据文件和全局变量中，数据文件中的值将覆盖全局变量中的值。如果数据目录或数据文件不存在，则只使用全局变量进行渲染。

### 部分模板命名规范

为了区分完整页面模板和部分模板（如布局、导航等），我们采用以下命名规范：

- 完整页面模板：使用`.twig`后缀，例如：`index.twig`、`about.twig`
- 部分模板：使用`.part.twig`后缀，例如：
  - `header.part.twig`：页头模板
  - `footer.part.twig`：页脚模板
  - `nav.part.twig`：导航模板
  - `sidebar.part.twig`：侧边栏模板
  - `layout.part.twig`：布局模板

在目录批量转换时，所有`.part.twig`后缀的文件都会被自动跳过，不会生成对应的HTML文件。

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