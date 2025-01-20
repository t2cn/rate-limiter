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
            echo "Create $dest ";

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
        static::findHelper();
        // 读取文件内容
        $content = file_get_contents($filePath);

        // 正则提取数组内容
        preg_match('/return\s*\[(.*?)\];/s', $content, $matches);
        dumpx($matches);

//        if (isset($matches[1])) {
//            // 获取数组内容
//            $arrayContent = $matches[1];
//            $lines        = explode("\n", $arrayContent);
//            $lastLine     = trim(end($lines));
//
//            // 判断数组最后一条记录的情况
//            if (empty($lastLine)) {
//                // 情况1：数组为空且 [] 在同一行
//                $arrayContent = "\n    $newItem";
//            } elseif (substr($lastLine, -1) === ',') {
//                // 情况2：最后一条数据有逗号
//                $arrayContent .= "\n    $newItem";
//            } else {
//                // 情况3：没有逗号，添加逗号再添加内容
//                $arrayContent .= ",\n    $newItem";
//            }
//
//            // 构造完整的文件内容并写回文件
//            $newContent = preg_replace('/return\s*\[.*\];/s', "return [$arrayContent\n];", $content);
//            file_put_contents($filePath, $newContent);
//
//            echo "Added $newItem to $filePath\n";
//        }
    }

    /**
     * FindHelper.
     * @return void
     */
    protected static function findHelper(): void
    {
        // Install.php in T2 engine
        require_once __DIR__ . '/vendor/framework/src/App/helpers.php';
    }
}