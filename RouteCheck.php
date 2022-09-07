<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RouteCheck extends Command
{
    protected $signature = 'custom:route-check';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $files = array_merge($this->listAllFiles(app_path() . '/'), $this->listAllFiles(resource_path('views/')));

        $foundDataSet = $this->populateDataList($files);

        $codeBlock = [];
        foreach ($foundDataSet as $data) {
            $descFile = addslashes($data[1]);
            $descRoute = addslashes(str_replace(["\n", '  '], '', $data[0]));
            $route = $data[0];
            $codeBlock[] = <<<EOF
try {
\$hints =  'LINE:' . __LINE__ . ' - $descFile::$descRoute - ';
$route;
} catch (\Exception \$e) {
    echo \$hints . \$e->getMessage() . "\n";
}
EOF;
        }

        $target = storage_path('test-route.php');
        file_put_contents($target, '<'.'?php ' . "\n" . $this->getDeclaim() . "\n" . implode("\n", $codeBlock));


        include $target;
    }

    function populateDataList(array $files)
    {
        $foundDataSet = [];
        foreach ($files as $file) {
            if ($file !== __FILE__) {
                $content = file_get_contents($file);
                if (preg_match_all('/route\([^\)]+,[^\[]*\[[^\]]+\]\s*\)/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $foundDataSet[] = [
                            $match,
                            $file
                        ];
                    }
                }
            }
        }
        return $foundDataSet;
    }

    function listAllFiles($dir) {
        $array = array_diff(scandir($dir), array('.', '..'));

        foreach ($array as &$item) {
            $item = $dir . $item;
        }
        unset($item);
        $files = [];
        foreach ($array as $item) {
            if (is_dir($item)) {
                $files = array_merge($files, $this->listAllFiles($item . DIRECTORY_SEPARATOR));
            } else {
                $files[] = $item;
            }
        }
        return $files;
    }

    public function getDeclaim()
    {
        // Any found claim from route
        return '
$this->aaa = random_int(1111, 9999);
$aaa = random_int(1111, 9999);
$object = (object)[
    "aaa" => random_int(1111, 9999),
];
$request = new class {
    public $aaa = 11111;
    function input() {
        return random_int(1111, 9999);
    }
};
';
    }
}
