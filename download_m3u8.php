<?php
download_m3u8('1.m3u8','1');

function download_m3u8($file,$dir = '')
{
	$content = file_get_contents($file);
	if (preg_match_all('/http:\/\/.*/', $content, $matches)) {
		print_r($matches);
		if (!$dir) {
			$dir = uniqid();
		}
		makedir($dir);
		echo "dir {$dir}\n\n";
		echo "download ts\n";
		$count = count($matches[0]);
		foreach ($matches[0] as $key => $value) {
			$ts_output = "{$dir}/{$key}.ts";
			$cmd = "curl -o {$ts_output} {$value}";
			exec($cmd);
			echo "\n$cmd\n";
			if (is_file($ts_output)) {
				$ts_outputs[] = $ts_output;
			}else{
				echo "create ts_output file failed ;\n $cmd";
				exit();
			}
		}
		if ($count>100) {
			$to_concat = array_chunk($ts_outputs,100);
		}else{
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
			}else{
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
		}else{
			$mp4_output = "{$dir}/output{$key}.mp4";
			rename($mp4_output,$last);
		}

		if (is_file($last)) {
			echo "\n\nsuccess {$last}\n";
		}else{
			echo "\n\nfailed\n";
		}
		

	}
}



function makedir($dir) {
	return is_dir($dir) or (makedir(dirname($dir)) and mkdir($dir, 0777));
}