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
     * @var array|string[]
     * 配置文件路径关系
     * 存储源目录（相对路径）和目标目录（相对路径或绝对路径）的关系，便于安装时的复制
     * 格式：[源目录 => 目标目录]
     */
    protected static array $pathRelation = [
        'config' => 'config',  // 这里表示将 'config' 目录复制到目标目录 'config'
    ];

    /**
     * 安装方法
     * 调用安装方法，执行安装过程。
     * 通过调用 `installByRelation` 方法来进行目录和文件的复制操作。
     * @return void
     */
    public static function install(): void
    {
        // 调用 `installByRelation` 方法进行文件和目录复制
        static::installByRelation();
    }

    /**
     * 卸载方法
     * 调用卸载方法，执行卸载过程。
     * 通过调用 `uninstallByRelation` 方法来删除目录和文件。
     *
     * @return void
     */
    public static function uninstall(): void
    {
        // 调用 `uninstallByRelation` 方法进行目录和文件删除
        self::uninstallByRelation();
    }

    /**
     * 根据路径关系进行安装操作
     * 该方法根据 `pathRelation` 数组中的源路径和目标路径关系，执行文件和目录的复制操作。
     * 它会检查目标路径的父目录是否存在，如果不存在则创建该目录，然后将源目录的内容复制到目标目录。
     * @return void
     */
    public static function installByRelation(): void
    {
        // 遍历路径关系数组，处理每一对源路径和目标路径
        foreach (static::$pathRelation as $source => $dest) {
            // 将源目录的文件和目录复制到目标目录
            copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
            // 输出日志，提示创建了目标路径
            echo "Create $dest \n";

            // 方法卸载这里。
            // 手动指定目标文件路径为 bootstrap.php
            $targetFilePath = base_path() . "/$dest/bootstrap.php";

            // 调用处理数组添加新项的方法
            static::addToArray($targetFilePath, 'T2\\RateLimiter\\Bootstrap::class');
        }
    }

    /**
     * 根据路径关系进行卸载操作
     * 该方法根据 `pathRelation` 数组中的目标路径，执行删除操作。
     * 它会检查目标路径是否存在，如果存在则删除文件或目录。
     * @return void
     */
    public static function uninstallByRelation(): void
    {
        // 遍历路径关系数组，处理每一对目标路径
        foreach (static::$pathRelation as $dest) {
            $file = base_path() . "/$dest/limiter.php"; // 目标文件路径

            // 如果目标文件存在，删除该文件
            if (is_file($file)) {
                unlink($file);
                echo "Deleted: $file\n";
            }
        }
    }

    /**
     * 添加新的内容到数组的最后一行
     * 读取并修改文件中的数组，然后保存回来。
     * @param string $filePath
     * @param string $newItem
     * @return void
     */
    protected static function addToArray(string $filePath, string $newItem): void
    {
        // 读取文件内容
        $fileContent = file_get_contents($filePath);

        // 正则提取数组内容
        preg_match('/return\s*\[(.*?)\];/s', $fileContent, $matches);

        if (isset($matches[1])) {
            // 获取数组内容
            $arrayContent = $matches[1];

            // 去除多余空格和换行符
            $str = preg_replace('/\s+/', '', $arrayContent);

            // 检查是否已经包含要追加的内容
            if (str_contains($str, $newItem)) {
                echo "The content already exists, no need to append.\n"; // 内容已存在，无需追加。
                return;
            }

            // 判断字符串是否以逗号结尾
            if (!str_ends_with($str, ',')) {
                $str .= ',';
            }

            // 追加内容
            $str .= $newItem;

            // 构建新的 return 内容
            $newReturnContent = "return [$str];";

            // 使用正则替换整个 return 语句
            $updatedContent = preg_replace(
                '/return\s*\[.*?\];/s', // 匹配 return 语句的正则
                $newReturnContent,      // 替换为新的 return 内容
                $fileContent
            );

            // 检查替换是否成功
            if ($updatedContent === null) {
                die("Regular match or replacement failed"); // 正则匹配或替换失败
            }

            // 将修改后的内容写回文件
            if (file_put_contents($filePath, $updatedContent) === false) {
                die("Unable to write to file $filePath"); // 无法写入文件
            }
        }
    }
}