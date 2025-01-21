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
        if (!self::isFrameworkInstalled(self::VALID_ENGINE_NAMES)) {
            echo "The 't2cn/framework' package is not installed. Installation aborted.\n";
            return;
        }
        foreach (static::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . "/$source";
            $targetPath = base_path() . "/$dest";
            // 拷贝文件
            if (!copy($sourcePath, $targetPath)) {
                echo "Failed to copy directory: $sourcePath to $targetPath\n";
            }
        }
        $bootstrapFilePath = base_path() . "/config/bootstrap.php";
        static::addToArray($bootstrapFilePath, 'T2\RateLimiter\Bootstrap::class');
        $middlewareFilePath = base_path() . "/config/middleware.php";
        static::addToArray($middlewareFilePath, 'T2\RateLimiter\Limiter::class');
    }

    /**
     * 给指定文件添加内容
     * @param string $filePath 要操作的文件路径
     * @param string $newItem 要增加的内容
     */
    protected static function addToArray(string $filePath, string $newItem): void
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }
        $fileContent = file_get_contents($filePath);
        if (!$fileContent) {
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
        switch ($newItem) {
            case 'T2\\RateLimiter\\Bootstrap::class':
            case 'T2\RateLimiter\Bootstrap::class':
                $arrayContent = preg_replace('/,(?!$)(?=\S)/', ',', $arrayContent);
                $arrayContent = rtrim($arrayContent, ',');
                $arrayContent .= ($arrayContent ? ',' : '') . $newItem;
                break;
            case 'T2\\RateLimiter\\Limiter::class':
            case 'T2\RateLimiter\Limiter::class':
                $oArrayContent = $arrayContent;
                if (!str_contains($arrayContent, '@')) {
                    $arrayContent = "'@'=>[" . $newItem . '],' . $oArrayContent;
                } else {
                    $atPosition           = strpos($arrayContent, '@');
                    $closeBracketPosition = strpos($arrayContent, ']', $atPosition);
                    if ($closeBracketPosition === false) {
                        echo "Closing bracket ']' not found.\n";
                        return;
                    }
                    $substring = substr($arrayContent, $atPosition - 1, $closeBracketPosition - $atPosition + 2);
                    if (!preg_match("/\[(.*?)\]/", $substring, $matches)) {
                        echo "No content found inside brackets.\n";
                        return;
                    }
                    $arrayContent = rtrim(preg_replace('/,\s*/', ',', $matches[1]), ',');
                    $arrayContent = rtrim($arrayContent, ',');
                    $arrayContent .= ($arrayContent ? ',' : '') . $newItem;
                    $newString    = "'@'=>[" . $arrayContent . ']';
                    $start        = $atPosition - 1;
                    $length       = $closeBracketPosition - $start + 1;
                    $arrayContent = substr_replace($oArrayContent, $newString, $start, $length);
                }
                break;
            default:
                echo "No action was taken\n";
        }
        $fileContent = preg_replace('/return\s*\[(.*?)\];/s', "return [$arrayContent];", $fileContent);
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
        $file = base_path() . "/config/limiter.php";
        if (is_file($file) && !unlink($file)) {
            echo "Failed to delete: $file\n";
        }
        $bootstrapFilePath = base_path() . "/config/bootstrap.php";
        static::removeFromArray($bootstrapFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        $middlewareFilePath = base_path() . "/config/middleware.php";
        static::removeFromArray($middlewareFilePath, 'T2\\RateLimiter\\Limiter::class');
    }

    /**
     * Remove an item from an array in a file
     * @param string $filePath
     * @param string $itemToRemove
     */
    protected static function removeFromArray(string $filePath, string $itemToRemove): void
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }
        $fileContent = file_get_contents($filePath);
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
        $updatedContent   = preg_replace('/return\s*\[.*?\];/s', $newReturnContent, $fileContent);
        if ($updatedContent === null || file_put_contents($filePath, $updatedContent) === false) {
            echo "Failed to write file: $filePath\n";
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
        if (!file_exists($composerFilePath)) {
            echo "composer.json not found. Cannot verify framework installation.\n";
            return false;
        }
        $composerContent = file_get_contents($composerFilePath);
        if ($composerContent === false) {
            echo "Failed to read composer.json.\n";
            return false;
        }
        $composerData = json_decode($composerContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Invalid composer.json format.\n";
            return false;
        }
        return in_array($composerData['name'], $haystack, true);
    }

}
