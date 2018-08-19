<?php

$longopt = array(
    'url:',
);
$params = getopt('', $longopt);
if (empty($params)) {
    echo "\n\nThis is a simple download m3u8 tool #_#\n
usage php download_m3u8.php --url \"{url}\"\n";
    exit();
}
if (empty($params['url'])) {
    echo "param url is needed\n";
}
$url = $params['url'];

download_m3u8($url);

function download_m3u8($url, $dir = '')
{
    $content = file_get_contents($url);
    echo $content;
    if (preg_match_all('/(http|https):\/\/.*/', $content, $matches) or preg_match_all('/.+\.ts/', $content, $matches)) {
        if (!$dir) {
            $dir = "video/" . md5($url);
        }
        makedir($dir);
        echo "dir {$dir}\n\n";
        echo "download ts\n";
        $count = count($matches[0]);
        foreach ($matches[0] as $key => $value) {
            if (!strpos($value, 'http')) {
                $parse_url_result = parse_url($url);
                $url_path = $parse_url_result['path'];
                $arr = explode('/', $url_path);
                array_splice($arr, -1);
                $url_path_pre = $parse_url_result['scheme'] . "://" . $parse_url_result['host'] . implode('/', $arr) . "/";
                $value = $url_path_pre . $value;
            }
            $ts_output = "{$dir}/{$key}.ts";
            $cmd = "curl -L -o {$ts_output} '{$value}'";
            exec($cmd);
            echo "\n$cmd\n";
            if (is_file($ts_output)) {
                $ts_outputs[] = $ts_output;
            } else {
                echo "create ts_output file failed ;\n $cmd";
                exit();
            }
        }
        if ($count > 100) {
            $to_concat = array_chunk($ts_outputs, 100);
        } else {
            $to_concat[] = $ts_outputs;
        }
        echo "concat ts to mp4\n";
        print_r($to_concat);
        foreach ($to_concat as $key => $value) {
            $str_concat = implode('|', $value);
            $mp4_output = "{$dir}/output{$key}.mp4";
            $cmd = "ffmpeg -i \"concat:{$str_concat}\" -acodec copy -vcodec copy -absf aac_adtstoasc {$mp4_output}";
            exec($cmd);
            echo "\n$cmd\n";
            if (is_file($mp4_output)) {
                $mp4_outputs[] = $mp4_output;
            } else {
                echo "create mp4_outputs file failed ;\n $cmd";
                exit();
            }
        }
        $last = "{$dir}/output.mp4";
        if (count($to_concat) > 1) {
            foreach ($mp4_outputs as $key => $value) {
                $fileliststr .= "file '{$value}'\n";
            }
            $filelist_file = "filelist.txt";
            file_put_contents($filelist_file, $fileliststr);

            $cmd = "ffmpeg -f concat -i {$filelist_file} -c copy {$last}";
            exec($cmd);
            echo "\n$cmd\n";
        } else {
            $mp4_output = "{$dir}/output{$key}.mp4";
            rename($mp4_output, $last);
        }

        if (is_file($last)) {
            $cmd = "rm -rf {$dir}/*ts";
            exec($cmd);
            echo "\n$cmd\n";

            echo "\n\nsuccess {$last}\n";
        } else {
            echo "\n\nfailed\n";
        }


    }
}


function makedir($dir)
{
    return is_dir($dir) or (makedir(dirname($dir)) and mkdir($dir, 0777));
}
