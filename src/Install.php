<?php

namespace T2\RateLimiter;

class Install
{
    /**
     * @var bool
     * T2 安装标识常量
     */
    const bool T2_INSTALL = true;

    /**
     * @var array 配置文件路径关系
     * 存储源目录（相对路径）和目标目录（相对路径或绝对路径）的关系，便于安装时的复制
     * 格式：[源目录 => 目标目录]
     */
    protected static array $pathRelation = [
        'config' => 'config',  // 这里表示将 'config' 目录复制到目标目录 'config'
    ];

    /**
     * 安装方法
     * 执行安装过程，调用 `installByRelation` 方法进行文件和目录复制。
     * @return void
     */
    public static function install(): void
    {
        static::installByRelation();
    }

    /**
     * 卸载方法
     * 执行卸载过程，调用 `uninstallByRelation` 方法删除文件和目录。
     * @return void
     */
    public static function uninstall(): void
    {
        static::uninstallByRelation();
    }

    /**
     * 根据路径关系进行安装操作
     * 遍历 `pathRelation` 数组，复制文件和目录。
     * @return void
     */
    public static function installByRelation(): void
    {
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source";
            $targetPath = base_path() . "/$dest";

            // 复制目录并检查结果
            if (!self::copyDirectory($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
                continue;
            }

            echo "Created: $targetPath\n";

            // 更新目标文件中的数组内容
            $targetFilePath = "$targetPath/bootstrap.php";
            static::addToArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

    /**
     * 根据路径关系进行卸载操作
     * 遍历 `pathRelation` 数组，删除目标文件。
     * @return void
     */
    public static function uninstallByRelation(): void
    {
        foreach (static::$pathRelation as $dest) {
            $file = base_path() . "/$dest/limiter.php";

            if (is_file($file)) {
                if (!unlink($file)) {
                    echo "Failed to delete: $file\n";
                } else {
                    echo "Deleted: $file\n";
                }
            }
        }
    }

    /**
     * 添加新的内容到数组的最后一行
     * 修改文件中的 `return` 数组内容并保存。
     * @param string $filePath 文件路径
     * @param string $newItem 要追加的内容
     * @return void
     */
    protected static function addToArray(string $filePath, string $newItem): void
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            echo "Failed to read file: $filePath\n";
            return;
        }

        // 提取数组内容
        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }

        $arrayContent = preg_replace('/\s+/', '', $matches[1]);

        // 检查是否已包含内容
        if (str_contains($arrayContent, $newItem)) {
            echo "Item already exists in array: $newItem\n";
            return;
        }

        // 构建新的内容
        $arrayContent     .= (!str_ends_with($arrayContent, ',') ? ',' : '') . $newItem;
        $newReturnContent = "return [$arrayContent];";

        // 替换并保存文件
        $updatedContent = preg_replace('/return\s*\[.*?\];/s', $newReturnContent, $fileContent);
        if ($updatedContent === null) {
            echo "Failed to update file: $filePath\n";
            return;
        }

        if (file_put_contents($filePath, $updatedContent) === false) {
            echo "Failed to write file: $filePath\n";
            return;
        }

        echo "Updated array in file: $filePath\n";
    }

    /**
     * 复制目录及其内容
     * @param string $source 源目录路径
     * @param string $destination 目标目录路径
     * @return bool 成功返回 true，失败返回 false
     */
    protected static function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            return false;
        }

        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath  = "$source/$item";
            $destPath = "$destination/$item";

            if (is_dir($srcPath)) {
                if (!self::copyDirectory($srcPath, $destPath)) {
                    return false;
                }
            } else {
                if (!copy($srcPath, $destPath)) {
                    return false;
                }
            }
        }

        return true;
    }
}