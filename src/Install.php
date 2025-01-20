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
            // 判断目标路径中的文件夹是否存在
            if ($pos = strrpos($dest, '/')) {
                // 提取目标路径的父目录
                $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
                // 如果父目录不存在，则创建该目录
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }
            // 将源目录的文件和目录复制到目标目录
            copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
            // 输出日志，提示创建了目标路径
            echo "Create $dest";
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
            // 构建目标路径的绝对路径
            $path = base_path() . "/$dest";
            // 如果目标路径不存在，则跳过
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }

            // 输出日志，提示删除了目标路径
            echo "Remove $dest";
            // 如果目标路径是文件或符号链接，则删除该文件
            if (is_file($path) || is_link($path)) {
                unlink($path);
                continue;
            }

            // 如果目标路径是目录，则递归删除目录及其内容
            remove_dir($path);
        }
    }
}