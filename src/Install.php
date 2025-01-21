<?php

namespace T2\RateLimiter;

class Install
{
    const bool T2_INSTALL = true;

    protected static array $pathRelation = [
        'config' => 'config',
    ];

    public static function install(): void
    {
        static::installByRelation();
    }

    public static function uninstall(): void
    {
        static::uninstallByRelation();
    }

    public static function installByRelation(): void
    {
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source";
            $targetPath = base_path() . "/$dest";

            if (!self::copyDirectory($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
                continue;
            }

            echo "Created: $targetPath\n";

            $targetFilePath = "$targetPath/bootstrap.php";
            static::addToArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

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

            $targetFilePath = base_path() . "/$dest/bootstrap.php";
            static::removeFromArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

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

    protected static function removeFromArray(string $filePath, string $itemToRemove): void
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

        if (!preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches)) {
            echo "No return array found in file: $filePath\n";
            return;
        }

        $arrayContent = preg_replace('/\s+/', '', $matches[1]);

        if (!str_contains($arrayContent, $itemToRemove)) {
            echo "Item not found in array: $itemToRemove\n";
            return;
        }

        $arrayContent = str_replace($itemToRemove . ',', '', $arrayContent); // 移除带逗号的项
        $arrayContent = str_replace($itemToRemove, '', $arrayContent);      // 移除最后的项

        $newReturnContent = "return [$arrayContent];";

        $updatedContent = preg_replace('/return\s*\[.*?\];/s', $newReturnContent, $fileContent);
        if ($updatedContent === null) {
            echo "Failed to update file: $filePath\n";
            return;
        }

        if (file_put_contents($filePath, $updatedContent) === false) {
            echo "Failed to write file: $filePath\n";
            return;
        }

        echo "Removed item from array in file: $filePath\n";
    }

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