<?php

namespace T2\RateLimiter;

class Install
{
    const bool T2_INSTALL = true;

    const array VALID_ENGINE_NAMES = ['t2cn/engine', 't2cn/engine-multiple'];

    /**
     * @var array
     */
    protected static array $pathRelation = [
        'config' => 'config',
    ];

    /**
     * Install the package
     * @return void
     */
    public static function install(): void
    {
        var_dump(!self::isFrameworkInstalled(self::VALID_ENGINE_NAMES));
        if (!self::isFrameworkInstalled(self::VALID_ENGINE_NAMES)) {
            // 如果没有安装 t2cn/framework 框架，退出不做任何操作
            echo "The 't2cn/framework' package is not installed. Installation aborted.\n";
            return;
        }

//        static::installByRelation();
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
     * Perform installation based on path relations
     * @return void
     */
    public static function installByRelation(): void
    {
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source";
            $targetPath = base_path() . "/$dest";

            if (!self::copyDirectory($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
            }
        }

        $bootstrapFilePath = base_path() . "/config/bootstrap.php";
        static::addToArray($bootstrapFilePath, 'T2\\RateLimiter\\Bootstrap::class');
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
     * Add an item to an array in a file
     * @param string $filePath
     * @param string $newItem
     */
    protected static function addToArray(string $filePath, string $newItem): void
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
        if (str_contains($arrayContent, $newItem)) {
            echo "Item already exists in array: $newItem\n";
            return;
        }

        $arrayContent     .= (!str_ends_with($arrayContent, ',') ? ',' : '') . $newItem;
        $newReturnContent = "return [$arrayContent];";
        self::updateFileContent($filePath, $fileContent, $newReturnContent);
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

    /**
     * Copy a directory
     * @param string $source
     * @param string $destination
     * @return bool
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
