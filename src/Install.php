<?php

namespace T2\RateLimiter;

class Install
{
    const bool T2_INSTALL = true;

    protected static array $pathRelation = [
        'config' => 'config',
    ];

    /**
     * 安装方法
     *
     * 检查 `t2cn/framework` 包是否已安装，如果已安装，则执行安装操作
     * 否则，输出错误信息并停止安装。
     */
    public static function install(): void
    {
        // 检查框架是否已安装
        if (!self::isFrameworkInstalled()) {
            echo "The 't2cn/framework' package is not installed. Installation aborted.\n";
            return;
        }

        // 执行安装操作
        static::installByRelation();
    }

    /**
     * 卸载方法
     *
     * 根据定义的路径关系卸载相关文件
     */
    public static function uninstall(): void
    {
        // 检查框架是否已安装
        if (!self::isFrameworkInstalled()) {
            echo "The 't2cn/framework' package is not installed. Installation aborted.\n";
            return;
        }

        static::uninstallByRelation();
    }

    /**
     * 根据路径关系执行安装操作
     *
     * 复制文件或目录，并更新相关配置
     */
    public static function installByRelation(): void
    {
        // 遍历路径关系数组进行处理
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source"; // 源路径
            $targetPath = base_path() . "/$dest"; // 目标路径

            // 复制目录或文件
            if (!self::copyDirectory($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
                continue;
            }

            echo "Created: $targetPath\n";

            // 更新 bootstrap.php 文件
            $targetFilePath = "$targetPath/bootstrap.php";
            static::addToArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

    /**
     * 根据路径关系执行卸载操作
     *
     * 删除文件或目录，并更新相关配置
     */
    public static function uninstallByRelation(): void
    {
        // 遍历路径关系数组进行处理
        foreach (static::$pathRelation as $dest) {
            $file = base_path() . "/$dest/limiter.php"; // 删除的文件

            // 删除文件
            self::deleteFile($file);

            // 更新 bootstrap.php 文件
            $targetFilePath = base_path() . "/$dest/bootstrap.php";
            static::removeFromArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

    /**
     * 检查 `t2cn/framework` 是否已安装
     *
     * 通过检查 `composer.json` 文件中的依赖项来验证框架是否安装。
     *
     * @return bool 返回框架是否已安装的状态
     */
    protected static function isFrameworkInstalled(): bool
    {
        $composerFilePath = base_path() . '/composer.json';

        // 检查 composer.json 文件是否存在
        if (!file_exists($composerFilePath)) {
            echo "composer.json not found. Cannot verify framework installation.\n";
            return false;
        }

        // 读取 composer.json 文件内容
        $composerContent = file_get_contents($composerFilePath);
        if ($composerContent === false) {
            echo "Failed to read composer.json.\n";
            return false;
        }

        // 解析 JSON 数据
        $composerData = json_decode($composerContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Invalid composer.json format.\n";
            return false;
        }

        // 检查是否存在 `t2cn/framework` 包
        $requirePackages = $composerData['require'] ?? [];
        return isset($requirePackages['t2cn/framework']);
    }

    /**
     * 向文件中的数组添加项
     *
     * 该方法会在文件中的数组中添加一个新项（如果该项不存在的话）。
     *
     * @param string $filePath 文件路径
     * @param string $newItem 要添加的项
     */
    protected static function addToArray(string $filePath, string $newItem): void
    {
        // 读取文件内容
        $fileContent = self::readFile($filePath);
        if ($fileContent === false) {
            return;
        }

        // 查找并提取数组内容
        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }

        // 格式化数组内容并检查是否已有该项
        $arrayContent = preg_replace('/\s+/', '', $matches[1]);
        if (str_contains($arrayContent, $newItem)) {
            echo "Item already exists in array: $newItem\n";
            return;
        }

        // 添加新项到数组末尾
        $arrayContent .= (!str_ends_with($arrayContent, ',') ? ',' : '') . $newItem;

        // 更新文件内容
        $newReturnContent = "return [$arrayContent];";
        self::updateFileContent($filePath, $fileContent, $newReturnContent);
    }

    /**
     * 从文件中的数组中移除项
     *
     * 该方法会从文件中的数组中移除指定的项（如果存在的话）。
     *
     * @param string $filePath 文件路径
     * @param string $itemToRemove 要移除的项
     */
    protected static function removeFromArray(string $filePath, string $itemToRemove): void
    {
        // 读取文件内容
        $fileContent = self::readFile($filePath);
        if ($fileContent === false) {
            return;
        }

        // 查找并提取数组内容
        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }

        // 格式化数组内容并检查是否存在该项
        $arrayContent = preg_replace('/\s+/', '', $matches[1]);
        if (!str_contains($arrayContent, $itemToRemove)) {
            echo "Item not found in array: $itemToRemove\n";
            return;
        }

        // 移除指定项
        $arrayContent = str_replace($itemToRemove . ',', '', $arrayContent); // 移除带逗号的项
        $arrayContent = str_replace($itemToRemove, '', $arrayContent);      // 移除最后的项

        // 更新文件内容
        $newReturnContent = "return [$arrayContent];";
        self::updateFileContent($filePath, $fileContent, $newReturnContent);
    }

    /**
     * 删除指定文件
     *
     * 封装删除文件的逻辑，便于复用
     *
     * @param string $filePath 要删除的文件路径
     */
    protected static function deleteFile(string $filePath): void
    {
        if (is_file($filePath)) {
            if (!unlink($filePath)) {
                echo "Failed to delete: $filePath\n";
            } else {
                echo "Deleted: $filePath\n";
            }
        }
    }

    /**
     * 读取文件内容
     *
     * 封装读取文件内容的逻辑，避免多次调用 `file_get_contents` 时代码重复
     *
     * @param string $filePath 文件路径
     * @return string|false 文件内容或失败时返回 false
     */
    protected static function readFile(string $filePath)
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return false;
        }

        return file_get_contents($filePath);
    }

    /**
     * 更新文件内容
     *
     * 更新文件内容的方法，替换原文件内容为新的内容
     *
     * @param string $filePath 文件路径
     * @param string $oldContent 旧的文件内容
     * @param string $newContent 新的文件内容
     */
    protected static function updateFileContent(string $filePath, string $oldContent, string $newContent): void
    {
        $updatedContent = preg_replace('/return\s*\[.*?\];/s', $newContent, $oldContent);
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
     * 复制目录或文件
     *
     * 复制目录及其内容到目标路径
     *
     * @param string $source 源路径
     * @param string $destination 目标路径
     * @return bool 是否复制成功
     */
    protected static function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        // 如果目标目录不存在，则创建
        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            return false;
        }

        // 递归复制目录内容
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