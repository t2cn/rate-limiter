<?php

namespace T2\RateLimiter;

class Install
{
    const bool T2_INSTALL = true;

    const array VALID_ENGINE_NAMES = ['t2cn/engine', 't2cn/engine-multiple'];

    /**
     * 定义源文件和目标文件
     * @var array
     */
    protected static array $pathRelation = [
        'config/limiter.php' => 'config/limiter.php'
    ];

    /**
     * Install the package
     * @return void
     */
    public static function install(): void
    {
        // 检查本地项目
        if (!self::isFrameworkInstalled(self::VALID_ENGINE_NAMES)) {
            echo "The 't2cn/framework' package is not installed. Installation aborted.\n";
            return;
        }

        // 循环遍历定义要操作的文件
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source"; // 源文件路径
            $targetPath = base_path() . "/$dest"; // 目标文件路径
            // 拷贝文件
            if (!copy($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
            }
        }

        // 更新 bootstrap.php 文件内容
        $bootstrapFilePath = base_path() . "/config/bootstrap.php"; // "/Users/dev/Develop.localized/scbtl/engine-multiple/config/bootstrap.php"
        static::addToArray($bootstrapFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        // 更新 middleware.php 文件内容
        $middlewareFilePath = base_path() . "/config/middleware.php"; // "/Users/dev/Develop.localized/scbtl/engine-multiple/config/middleware.php"
        static::addToArray($middlewareFilePath, 'T2\\RateLimiter\\Limiter::class');
    }

    /**
     * 给指定文件添加内容
     * @param string $filePath 要操作的文件路径
     * @param string $newItem 要增加的内容
     */
    protected static function addToArray(string $filePath, string $newItem): void
    {
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }

        // 读取文件内容
        $fileContent = file_get_contents($filePath);
        if (!$fileContent) {
            echo "Failed to read file: $filePath\n";
            return;
        }

        // 从文件中提取数组内容
        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }
        // 获取数组内容并去除空格、换行符等
        $arrayContent = preg_replace('/\s+/', '', $matches[1]);
        switch ($newItem) {
            case 'T2\\RateLimiter\\Bootstrap::class':
            case 'T2\RateLimiter\Bootstrap::class':
                // 检查要添加的内容是否已经存在
                if (str_contains($arrayContent, $newItem)) {
                    echo "Item already exists in array: $newItem\n";
                    return;
                }
                // 格式化数组内容，确保逗号后有空格
                $arrayContent = preg_replace('/,(?!$)(?=\S)/', ', ', $arrayContent);
                // 去掉末尾的逗号和空格
                $arrayContent = rtrim($arrayContent, ', ');
                // 如果数组内容已经有项，添加逗号和空格，再追加新项
                $arrayContent .= ($arrayContent ? ', ' : '') . $newItem;
                // 更新文件内容
                $fileContent = preg_replace('/return\s*\[(.*?)\];/s', "return [$arrayContent];", $fileContent);
                break;
            case 'T2\\RateLimiter\\Limiter::class':
            case 'T2\RateLimiter\Limiter::class':
                if (!str_contains($arrayContent, '@')) {
                    echo "'@' not found in the string.\n";
                    return;
                }
                // 查找 '@' 符号的起始位置
                $atPosition = strpos($arrayContent, '@');
                // 查找 '@' 后面第一个 ']' 的位置
                $closeBracketPosition = strpos($arrayContent, ']', $atPosition);
                if ($closeBracketPosition === false) {
                    echo "Closing bracket ']' not found.\n";
                    return;
                }
                // 截取 '@' 前一位到 ']' 后一位的字符串
                $substring = substr($arrayContent, $atPosition - 1, $closeBracketPosition - $atPosition + 2);
                var_dump($substring);
                // 使用正则表达式提取中括号内的内容
                if (!preg_match("/\[(.*?)\]/", $substring, $matches)) {
                    echo "No content found inside brackets.\n";
                    return;
                }
                // 提取中括号内的内容并进行格式化
                $arrayContent = rtrim(preg_replace('/,\s*/', ',', $matches[1]), ',');
                // 添加新的内容到数组中
                $arrayContent .= ($arrayContent ? ',' : '') . "\\T2\\RateLimiter\\LimiterB::class";
                // 构造替换后的 '@' 区间字符串
                $newString = "'@'=>[" . $arrayContent . ']';
                var_dump($newString);
                // 确定替换的起点和长度
                $start  = $atPosition - 1;
                $length = $closeBracketPosition - $start + 1;
                // 替换原字符串的指定部分
                $arrayContent = substr_replace($arrayContent, $newString, $start, $length);
                var_dump($arrayContent);
                break;
            default:
                echo "No action was taken\n";
        }

        // 写回更新后的文件内容
        if (file_put_contents($filePath, $fileContent) === false) {
            echo "Failed to update file: $filePath\n";
        }
    }

    /**
     * Uninstall the package
     * @return void
     */
    public static function uninstall(): void
    {
        foreach (static::$pathRelation as $dest) {
            $file = base_path() . "/$dest/limiter.php";
            self::deleteFile($file);

            $targetFilePath = base_path() . "/$dest/bootstrap.php";
            static::removeFromArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

    /**
     * Check if the framework is installed
     * @param array $haystack 要对比的名称
     * @return bool
     */
    protected static function isFrameworkInstalled(array $haystack): bool
    {
        $composerFilePath = base_path() . '/composer.json';
        // 没有正确的 composer.json 文件
        if (!file_exists($composerFilePath)) {
            echo "composer.json not found. Cannot verify framework installation.\n";
            return false;
        }
        $composerContent = file_get_contents($composerFilePath);
        // 读取 composer.json 文件失败
        if ($composerContent === false) {
            echo "Failed to read composer.json.\n";
            return false;
        }
        $composerData = json_decode($composerContent, true);
        // composer.json 格式无效
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Invalid composer.json format.\n";
            return false;
        }
        return in_array($composerData['name'], $haystack, true);
    }

    /**
     * Remove an item from an array in a file
     * @param string $filePath
     * @param string $itemToRemove
     */
    protected static function removeFromArray(string $filePath, string $itemToRemove): void
    {
        $fileContent = self::readFile($filePath);
        if ($fileContent === false) {
            return;
        }

        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }

        $arrayContent = preg_replace('/\s+/', '', $matches[1]);
        if (!str_contains($arrayContent, $itemToRemove)) {
            echo "Item not found in array: $itemToRemove\n";
            return;
        }

        $arrayContent     = str_replace($itemToRemove . ',', '', $arrayContent);
        $arrayContent     = str_replace($itemToRemove, '', $arrayContent);
        $newReturnContent = "return [$arrayContent];";
        self::updateFileContent($filePath, $fileContent, $newReturnContent);
    }

    /**
     * Delete a file
     * @param string $filePath
     */
    protected static function deleteFile(string $filePath): void
    {
        if (is_file($filePath) && !unlink($filePath)) {
            echo "Failed to delete: $filePath\n";
        }
    }

    /**
     * Read file content
     * @param string $filePath
     * @return string|false
     */
    protected static function readFile(string $filePath): false|string
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return false;
        }

        return file_get_contents($filePath);
    }

    /**
     * Update file content
     * @param string $filePath
     * @param string $oldContent
     * @param string $newContent
     */
    protected static function updateFileContent(string $filePath, string $oldContent, string $newContent): void
    {
        $updatedContent = preg_replace('/return\s*\[.*?\];/s', $newContent, $oldContent);
        if ($updatedContent === null || file_put_contents($filePath, $updatedContent) === false) {
            echo "Failed to write file: $filePath\n";
        }
    }
}
