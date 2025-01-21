<?php

namespace InstallUtils;

class ComponentInstaller
{
    const T2_INSTALL = true; // 安装模式标记

    /**
     * 安装组件
     *
     * @param string $source 源路径
     * @param string $destination 目标路径
     */
    public static function install(string $source, string $destination): void
    {
        $sourcePath      = self::getPath(__DIR__, $source);
        $destinationPath = self::getPath(__DIR__, $destination);

        if (!is_dir($sourcePath)) {
            throw new \RuntimeException("Source directory not found: $sourcePath");
        }

        self::copyDirectory($sourcePath, $destinationPath);
        echo "Component installed from $sourcePath to $destinationPath\n";
    }

    /**
     * 卸载组件
     *
     * @param string $path 目标路径
     */
    public static function uninstall(string $path): void
    {
        $destinationPath = self::getPath(__DIR__, $path);

        if (!is_dir($destinationPath)) {
            throw new \RuntimeException("Directory not found: $destinationPath");
        }

        self::deleteDirectory($destinationPath);
        echo "Component uninstalled from $destinationPath\n";
    }

    /**
     * 更新文件内容
     *
     * @param string $filePath 文件路径
     * @param string $oldContent 旧内容
     * @param string $newContent 新内容
     */
    public static function updateFileContent(string $filePath, string $oldContent, string $newContent): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }

        $fileContent    = file_get_contents($filePath);
        $updatedContent = str_replace($oldContent, $newContent, $fileContent);

        file_put_contents($filePath, $updatedContent);
        echo "File updated: $filePath\n";
    }

    /**
     * 从数组中移除元素
     *
     * @param string $arrayContent 数组字符串
     * @param string $itemToRemove 要移除的元素
     * @return string
     */
    public static function removeFromArray(string $arrayContent, string $itemToRemove): string
    {
        $pattern = '/\s*' . preg_quote($itemToRemove, '/') . '\s*,?/';
        return preg_replace($pattern, '', $arrayContent);
    }

    /**
     * 添加元素到数组
     *
     * @param string $arrayContent 数组字符串
     * @param string $itemToAdd 要添加的元素
     * @return string
     */
    public static function addToArray(string $arrayContent, string $itemToAdd): string
    {
        $array = self::parseArrayFromString($arrayContent);
        if (!in_array($itemToAdd, $array, true)) {
            $array[] = $itemToAdd;
        }

        return self::arrayToString($array);
    }

    /**
     * 解析数组字符串为数组
     *
     * @param string $arrayContent 数组字符串
     * @return array
     */
    protected static function parseArrayFromString(string $arrayContent): array
    {
        return eval('return ' . $arrayContent . ';'); // 使用更安全的逻辑替代 eval
    }

    /**
     * 将数组转换为字符串
     *
     * @param array $array
     * @return string
     */
    protected static function arrayToString(array $array): string
    {
        return '[' . implode(", ", array_map('var_export', $array, [true])) . ']';
    }

    /**
     * 获取路径
     *
     * @param string $base 基础路径
     * @param string $relative 相对路径
     * @return string
     */
    protected static function getPath(string $base, string $relative): string
    {
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    /**
     * 递归复制目录
     *
     * @param string $source 源目录
     * @param string $destination 目标目录
     */
    protected static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        foreach (scandir($source) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath  = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcPath)) {
                self::copyDirectory($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }

    /**
     * 递归删除目录
     *
     * @param string $directory
     */
    protected static function deleteDirectory(string $directory): void
    {
        foreach (scandir($directory) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($directory);
    }
}
