<?php

	require_once('./include/common.php');

	if(isset($argv[1])){
		$csv = csv_to_array($argv[1]);
	} else {
		print "CSVファイルを指定してください";
		exit;
	}

	//完了済みを省く
	if(END_ORDER){
		$csv = end_del($csv);
	}

	if(count($csv) !== 0 && isset($csv[0]['Name'])){
		//個別注文ごとの処理
		foreach($csv as $k=> $v){
			$keys = get_template_names($v);
			foreach($keys as $k2=> $v2){
				create_pdf_one($v, $v2);
			}
			print $v["Name"]."...作成完了\n";
		}

		//印刷用にまとめる処理
		$draft_list = draft_list($csv);
		foreach($draft_list as $k=>$v){
			create_pdf($k, $v);
		}
		print "印刷データ...作成完了";

		//完了済みを保存-----------------
		$end_text = trim(file_get_contents('end_order.txt'));
		$end = "";
		foreach($csv as $v){
			if(strpos($end_text, $v['Name']) === false){
				$end .= $v['Name']."\r\n";
			}
		}
		file_put_contents('end_order.txt', $end, FILE_APPEND);
		//-----------------------------
	} else {
		print "新しい注文情報がありませんでした";
	}
	
	sleep(1);

?>