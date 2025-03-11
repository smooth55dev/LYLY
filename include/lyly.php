<?php

	//===============================
	//	lyly用関数群
	//===============================
	use Yasumi\Yasumi;


	//CSVファイルを配列化して返す
	function csv_to_array($file_path){
		mb_internal_encoding('UTF-8');

		$file = fopen($file_path, 'r');
		$headers = fgetcsv($file);

		// 各行を処理
		$no = 0;
		while (($row = fgetcsv($file)) !== false) {
			if(count($headers) !== count($row)){
				print "CSV Error 項目数が一致しません";
				exit;
			}
			$tmp = array_combine($headers, $row);
			if(strpos($tmp['Name'], '#') === 0 && is_numeric($tmp['Order ID'])){
				$no = 1;
				$row[1] = $row[1].'('.$no.')';
				$data[] = array_combine($headers, $row);
			} else {
				$no++;
				$row[1] = $row[1].'('.$no.')';
				$data[] = array_combine($headers, $row);
			}
		}
		fclose($file);
		return $data;
	}

	//作成するPDFの種類を取得
	function get_template_names($val){
		//縦横判定
		$tateyoko = '';
		if(mb_strpos($val['Product Title'], 'シンプルデザイン')){
			if(mb_strpos($val['写真の形'], '縦長') === 0){
				$tateyoko = "縦";
			} else if(mb_strpos($val['写真の形'], '横長') === 0){
				$tateyoko = "横";
			}
		}
		if(isset($val['Product Title'])){
			if(mb_strpos($val['Product Title'], 'アクリルパネル') === 0){
				if(isset($val['Variant Option 1']) && mb_strpos($val['Variant Option 1'], 'M') === 0){
					return NAMES[$val['Product Title']."M".$tateyoko];
				} else {
					return NAMES[$val['Product Title']."S".$tateyoko];
				}
			} else {
				if(mb_strpos($val['Product Title'], 'アクリル時計') === 0){
					return NAMES[$val['Product Title'].$val['文字盤の色']];
				} else {
					return NAMES[$val['Product Title'].$tateyoko];
				}
			}
		}
	}

	//デザインテンプレート取得
	function get_template($key){
		return TEMPLATES[$key];
	}
	function set_template($template, $csv, $key){
		//置換処理
		foreach($template as $k=>$v){
			//image
			if($v['type'] == 'image'){
				$url = '';

				if($v['name'] == '写真横'){
					if(isset($csv['写真横']) && trim($csv['写真横'])){
						$url = $csv['写真横'];
					}
				} else if($v['name'] == '写真縦'){
					if(isset($csv['写真縦']) && trim($csv['写真縦'])){
						$url = $csv['写真縦'];
					}
				} else if($v['name'] == '写真'){
					//写真の場合存在するものを優先的に入れておく
					if(trim($csv['写真'])){
						$url = $csv['写真'];
					} else if(trim($csv['写真縦'])){
						$url = $csv['写真縦'];
					} else if(trim($csv['写真横'])){
						$url = $csv['写真横'];
					}
				}

				if(trim($url) == ''){
					$v['E'] = '写真URLが存在しない';
				} else {
					$file_path = './download/'.$csv['Name'].".jpg";
					//ローカルにある場合スキップ
					if(file_exists($file_path)){
						$v['file'] = $file_path;
					} else if($url !='') {
						try {
							$image_data = file_get_contents($url);
							if($image_data === false){
								$v['E'] = '画像ダウンロード失敗';
							} else {
								file_put_contents($file_path, $image_data);
								if (filesize($file_path) === 0) {
									$v['E'] = '0バイトの画像';
									unlink($file_path);
								} else {
									convert_to_jpeg($file_path);
									$v['file'] = $file_path;
								}
							}
						} catch(Exception $e){
							$v['E'] = '画像の問題';
							print "画像エラー";
						}
					}
				}
			}
			//text
			if($v['type'] == 'text'){
				if(isset($csv[$v['name']])){
					$v['value'] = $csv[$v['name']];
				}
				//font(_***_editor)
				if(isset($csv['_'.$v['name'].'_editor']) && trim($csv['_'.$v['name'].'_editor']) != ''){
					$str = $csv['_'.$v['name'].'_editor'];
					$font = get_font($str);
					$size = get_fontsize($str);

					if($font){
						$v['font'] = $font;
					}
					// font sizeはtemplate通り
					//if($size){
					//	$v['font_size'] = $size;
					//}
				}
			}
			//calendar
			if($v['type'] == 'calendar'){
				if(isset($csv[$v['name']])){
					$v['date'] = $csv[$v['name']];
				}
			}

			//エラーが存在する場合
			if(isset($v['E'])){
				e($csv['Name'].' '.$v['E']);
			}

			//set
			$template[$k] = $v;
		}
		return $template;
	}

	//jpgに変換(tcpdfの為)
	function convert_to_jpeg($file_path) {
		// 画像の種類を判定し、JPEGに変換
		if (is_readable($file_path)) {
			try {
				$image = @imagecreatefromstring(file_get_contents($file_path));
				if ($image !== false) {
					imagejpeg($image, $file_path, 100);
					imagedestroy($image);
				}
			} catch(Exception $msg) {
				e($file_path." 画像の問題 ".$msg);
			}
		}
	}

	//個別のPDF作成
	function create_pdf_one($val, $key){
		$save_path = './temp/'.$val['Name'].'_'.$key.'.pdf';

		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi();
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);

		foreach(FONTS as $v){
			//$tcpdf->AddFont($v, '', './'.$v.'.ttf', true);
		}

		//========================

		//各デザイン処理===========
		$template = get_template($key);
		$template = set_template($template, $val, $key);

		//BASE描画
		$load_path = './parts/'.$key.".pdf";
		$tcpdf->setSourceFile($load_path);
		$tpl = $tcpdf->importPage(1);
		$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);

		//text, image
		if($template){
			foreach($template as $v){
				if($v['type'] == 'text'){
					//type
					$font_type = '';
					if(isset($v['font_type'])){
						$font_type = $v['font_type'];
					}
					//color
					if(isset($v['font_color'])){
						$tmp = explode(',',$v['font_color']);
						if(count($tmp) == 4){
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2],$tmp[3]);
						} else if(count($tmp) == 3) {
							$tcpdf->SetTextColor($tmp[0],$tmp[1],$tmp[2]);
						}
					} else {
						$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
					}
					if(isset($v['auto_size']) && $v['auto_size'] == 1){
						$tcpdf->SetXY($v['x'], $v['y']);
						for ($i = $v['font_size']; $i >= AUTO_SIZE_MIN; $i -= 0.001){
							$tcpdf->SetFont($v['font'], $font_type, $i);
							if ($tcpdf->GetStringWidth($v['value']) <= $v['w']){
								break;
							}
						}
						$tcpdf->Cell($v['w'], $v['h'], $v['value'], TEXT_BORDER, 1, $v['align'], 0, '', 0, false, 'T', 'M');
					} else {
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($v['font'], $font_type, $v['font_size']);
						$tcpdf->Cell($v['w'], $v['h'], $v['value'], TEXT_BORDER, 1, $v['align']);
					}
				} else if($v['type'] == 'image'){
					if($v['file']){
						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$mimeType = finfo_file($finfo, $v['file']);
						finfo_close($finfo);
						if ($mimeType === 'image/jpeg') {
							$tcpdf->Image($v['file'], $v['x'], $v['y'], $v['w'], $v['h'], 'JPG', '', 'C', true, 1200);
						}
					}
				} else if($v['type'] == 'rect'){
					$tcpdf->Rect($v['x'], $v['y'], $v['w'], $v['h'], 'F', array(), array(0,0,0));
				} else if($v['type'] == 'rectline'){
					$tcpdf->SetLineWidth($v['border_width']*0.3759);
					$tcpdf->SetDrawColor(0, 0, 0);
					$tcpdf->Rect($v['x'], $v['y'], $v['w'], $v['h'], 'D');
				} else if($v['type'] == 'pdf'){
					$tcpdf->setSourceFile('./parts/'.$v['file']);
					$tpl = $tcpdf->importPage(1);
					$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);					
				} else if($v['type'] == 'calendar'){
					//デザイン調整変数-------------
					if($v['size'] == 's'){
						$spx = 2;
						$spy = 28.3;
						$dmx = 8.2;
						$dmy = 5.6;
						$fs = 6;
						$font = "Shippori Mincho";
					} else if($v['size'] == 'm'){
						$spx = 3;
						$spy = 40;
						$dmx = 11.5;
						$dmy = 8;
						$fs = 9;
						$font = "Shippori Mincho";
					}
					//----------------------------

					$dt = new DateTime($v['date']);

					// 年、月、日、曜日を抽出
					$year  = $dt->format('Y');
					$month = $dt->format('m');
					$day   = $dt->format('d');
					$weekday = $dt->format('w'); // 0:日曜日, 1:月曜日, ...

					//月名
					$month_name = ['January','February','March','April','May','June','July','August','September','October','November','December',];

					if($v['size'] == 's'){
						//year
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($font, '', '12.65');
						$tcpdf->Cell(56, 4.5, $year, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month num
						$tcpdf->SetXY($v['x']+1.8, $v['y']+5.5);
						$tcpdf->SetFont($font, $font_type, 28.55);
						$tcpdf->Cell(10, 10, (int)$month, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month name
						$tcpdf->SetXY($v['x'], $v['y']+11);
						$tcpdf->SetFont($font, $font_type, 12.69);
						$tcpdf->Cell(56, 4.5, $month_name[(int)$month-1], 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					} else if($v['size'] == 'm'){
						//year
						$tcpdf->SetXY($v['x'], $v['y']);
						$tcpdf->SetFont($font, '', '17.85');
						$tcpdf->Cell(78, 4.5, $year, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month num
						$tcpdf->SetXY($v['x']+1, $v['y']+7.8);
						$tcpdf->SetFont($font, $font_type, 40.15);
						$tcpdf->Cell(15, 15, (int)$month, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
						//month name
						$tcpdf->SetXY($v['x'], $v['y']+16);
						$tcpdf->SetFont($font, $font_type, 17.85);
						$tcpdf->Cell(78, 7.5, $month_name[(int)$month-1], 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					}

					$calendar = get_calendar($v['date']);
					//days
					foreach($calendar['days'] as $ck => $cv){
						$px = ($ck+(int)$calendar['week'])%7*$dmx;
						$py = floor(($ck+(int)$calendar['week'])/7)*$dmy;
						//ハート
						$tmp1 = new DateTime($v['date']);
						$tmp2 = new DateTime($cv['day']);
						if($tmp1 == $tmp2){
							if(isset($v['black']) && $v['black']){
								if($v['size'] == 's'){
									$tcpdf->ImageSVG($file='./parts/calendar_s_heart_black.svg', $x=$v['x']+$spx+$px-1.5, $y=$v['y']+$spy+$py-0.75, $w='6.06', $h='4.74', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								} else if($v['size'] == 'm'){
									$tcpdf->ImageSVG($file='./parts/calendar_m_heart_black.svg', $x=$v['x']+$spx+$px-2.75, $y=$v['y']+$spy+$py-1.2, $w='8.516', $h='6.665', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								}
							} else {
								if($v['size'] == 's'){
									$tcpdf->ImageSVG($file='./parts/calendar_s_heart.svg', $x=$v['x']+$spx+$px-1.5, $y=$v['y']+$spy+$py-0.75, $w='6.06', $h='4.74', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								} else if($v['size'] == 'm'){
									$tcpdf->ImageSVG($file='./parts/calendar_m_heart.svg', $x=$v['x']+$spx+$px-2.75, $y=$v['y']+$spy+$py-1.2, $w='8.516', $h='6.665', $link='', $align='', $palign='', $border=0, $fitonpage=false);
								}
							}
						}
						//各日表示
						if(isset($v['black']) && $v['black']){
							$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
						} else {
							if($cv['week'] == 'red'){
								$tcpdf->SetTextColor(31, 96, 89, 0);
							} else if($cv['week'] == 'blue'){
								$tcpdf->SetTextColor(86, 52, 0, 0);
							} else {
								$tcpdf->SetTextColor(0, -1, -1, -1, false, '');
							}
						}
						$tcpdf->SetXY($v['x']+$spx+$px, $v['y']+$spy+$py);
						$tcpdf->SetFont($font, $font_type, $fs);
						$tcpdf->Cell(3, 3, day_cut($cv['day']), 0, 1, 'C', 0, '', 0, false, 'T', 'M');
					}
				}
			}
		}
		//=======================

		//保存処理================
		$tcpdf->Output($save_path, "F");
		//=======================

		//カレンダー(横形式)のは縦に回転しておく
		if($key == 'calendar_s_text' || $key == 'calendar_s_fullcolor' || $key == 'calendar_s_futa'){
			rotate_one($save_path, 129.52, 91);
		} else if($key == 'calendar_m_text' || $key == 'calendar_m_fullcolor' || $key == 'calendar_m_futa'){
			rotate_one($save_path, 182.05, 128);
		}
	}


	


	//まとめたPDFの作成
	function create_pdf($k, $v){
		//座標ループ
		$xys = DRAFTS_XYS[$k];
		//作成するPDF個数
		$limit = count($xys);
		$page_max = ceil(count($v)/$limit);

		for($j=0; $j < $page_max; $j++){

			//初期設定=================
			$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi();
			$tcpdf->setPrintHeader(false);
			$tcpdf->setPrintFooter(false);
			$tcpdf->SetMargins(0, 0, 0);
			$tcpdf->SetAutoPageBreak(false, 0);
			$tcpdf->AddPage();
			$tcpdf->setFontSubsetting(false);
			//========================

			//BASE描画(サイズ用)-------------
			$tcpdf->setSourceFile('./parts/draft_base.pdf');
			$tpl = $tcpdf->importPage(1);
			$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);
			//------------------------------

			$doc_size = $tcpdf->getTemplateSize($tpl);
			$ax = $doc_size['width']/2;
			$ay = $doc_size['height']/2;
			//---------------------

			//ページごとの初期値
			$start = $j*$limit;
			$ids = '';

			for($i=0; $i < count($xys); $i++){
				$k2 = $start + $i;
				$v2 = $xys[$i];

				//IDS
				if(isset($v[$k2])){
					if($ids == ''){
						$ids .= get_id($v[$k2]);
					} else {
						$ids .= ','.get_id($v[$k2]);
					}
				}

				//各PDF配置
				if(isset($v[$k2])){
					//描画しない例外処理---------
					$flag = true;
					if($k === 's_futa' && strpos($v[$k2], 'led')){
						$flag = false;
					} else if($k === 's_led_futa' && !strpos($v[$k2], 'led')){
						$flag = false;
					}
					//-------------------------

					$x = floatval($v2[0])+$ax;
					$y = floatval($v2[1])+$ay;
					$path = './temp/'.$v[$k2];

					if(file_exists($path)) {
						$tcpdf->setSourceFile($path);
						$tpl = $tcpdf->importPage(1);
						if($flag){
							$tcpdf->useTemplate($tpl, $x, $y);
						}
						$doc_size_one = $tcpdf->getTemplateSize($tpl);

						//rect test-------------
						//$tcpdf->SetAlpha(0.1);
						//$tcpdf->Rect($x, $y, $doc_size_one['width'], $doc_size_one['height'], 'F');
						//$tcpdf->SetAlpha(1);
						//----------------------
					}

					//ファイル名表示---------
					if(NAME_DISPLAY){
						$tcpdf->SetXY($x+$doc_size_one['width']+6, $y);
						$tcpdf->SetFont('helvetica', '', 8);
						$tcpdf->StartTransform();
						$tcpdf->Rotate(270, $x+$doc_size_one['width']+6, $y);
						$tcpdf->Cell(0, 0, '['.str_replace('./temp/', '', $path).']', 0, 1, 'L');
						$tcpdf->StopTransform();
					}
					//----------------------
				}
			}
			//枠描画
			$tcpdf->setSourceFile('./parts/'.draft_base_name($k));
			$tpl = $tcpdf->importPage(1);
			$tcpdf->useTemplate($tpl,['adjustPageSize' => true]);

			//保存処理================
			//idsいらない
			//$file_path = './draft/'.get_time().($j+1).'_'.$k.'_'.$ids.'.pdf';
			$file_path = './draft/'.get_time().($j+1).'_'.$k.'.pdf';
			$tcpdf->Output($file_path, "F");
			if(ROTATE){
				rotate($file_path);
			}
			//=======================
		}
	}

	//CSV配列から印刷用PDFに処理しやすい配列を作成して返す
	function draft_list($list){
		$data = [];
		foreach($list as $v){
			$names = get_template_names($v);
			//print_r($names);
			foreach($names as $v2){
				for($i=0; $i < order_count($v['Line Item Quantity']); $i++){
					$data[$v2][] = $v['Name'];
				}
			}
		}
		$ret = [];

		foreach(DRAFTS as $k => $v){
			foreach($v as $v2){
				if(isset($data[$v2])){
					foreach($data[$v2] as $v3){
						$ret[$k][] = $v3.'_'.$v2.'.pdf';
					}
				}
			}
		}
		return $ret;
	}

	//数値化
	function order_count($val){
		return intval($val);
	}
	//#以下の数字を抽出
	function get_id($str){
		$p = '/#\d+/';
		if (preg_match($p, $str, $m)) {
			return $m[0];
		}
	}
	//今の時間を取得(ファイル名用)
	function get_time(){
		return date('Ymd_Hi_');
	}


	function draft_base_name($val){
		if(strpos($val,'s') === 0){
			return 'draft_s.pdf';
		} else if(strpos($val,'m') === 0){
			return 'draft_m.pdf';
		} else if(strpos($val,'block') === 0){
			return 'draft_block.pdf';
		} else if(strpos($val,'tokei') === 0){
			return 'draft_tokei.pdf';
		}
	}

	//90度回転して保存(draft用)
	function rotate($path){
		//全て同じサイズ
		$w=335.5;
		$h=775.5;

		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi('L','mm', array($h, $w));
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);
		//========================

		$tcpdf->setSourceFile($path);
		$tpl = $tcpdf->importPage(1);

		//775,335
		$tcpdf->StartTransform();
		$tcpdf->Rotate(90, 0, 0);
		$tcpdf->useTemplate($tpl, -$w, 0);
		$tcpdf->StopTransform();
		$tcpdf->Output($path, "F");
	}

	//カレンダーなど用
	function rotate_one($path, $w, $h){
		//初期設定=================
		$tcpdf = new setasign\Fpdi\Tcpdf\Fpdi('','mm', array($h, $w));
		$tcpdf->setPrintHeader(false);
		$tcpdf->setPrintFooter(false);
		$tcpdf->SetMargins(0, 0, 0);
		$tcpdf->SetAutoPageBreak(false, 0);
		$tcpdf->AddPage();
		$tcpdf->setFontSubsetting(false);
		//========================

		$tcpdf->setSourceFile($path);
		$tpl = $tcpdf->importPage(1);

		$tcpdf->StartTransform();
		$tcpdf->Rotate(270, 0, 0);
		$tcpdf->useTemplate($tpl, 0, -$h);
		$tcpdf->StopTransform();
		$tcpdf->Output($path, "F");
	}


	//完了済みを削除
	function end_del($csv_base){
		$csv = [];
		//完了済み情報を取得
		$end_text = file_get_contents('end_order.txt');
		$end_list = explode("\r\n", trim($end_text));
	
		foreach($csv_base as $v){
			$flag = true;
			foreach($end_list as $v2){
				if(trim($v2) == $v['Name']){
					$flag = false;
				}
			}
			if($flag){
				$csv[] = $v;
			}
		}
		return $csv;
	}

	//フォントの種類を返す
	function get_font($str){
		$p = "/font:(\d+)/";
		preg_match($p, $str, $m);
		
		if (count($m) > 1) {
			return FONTS[$m[1]];
		} else {
			return false;
		}
	}
	//フォントサイズを返す
	function get_fontsize($str){
		$p = "/size:(\d+)/";
		preg_match($p, $str, $m);
		
		if (count($m) > 1) {
			$pt = $m[1] * (72 / 150);
			return $pt;
		} else {
			return false;
		}
	}

	function e($msg){
		$log = trim(file_get_contents('log.txt'));
		$log = $msg.$log;
		file_put_contents('log.txt', $msg."\r\n");
	}

	//始まりの曜日と、各日の休日判定済みリストを返す
	function get_calendar($d){
		$dt = new DateTime($d);
		$year  = $dt->format('Y');
		$month = $dt->format('m');
		$day   = $dt->format('d');
		$ym = $year.'-'.$month;
		
		$list = [];
	
		//祭日Yasumi取得-----------
		$holidays = Yasumi::create('Japan', $year, 'ja_JP');

		foreach ($holidays->getHolidayDates() as $date) {
			if(strpos($date, $ym) !== false){
				$list[] = $date;
			}
		}
		//-------------------------

	
		$last = (new DateTimeImmutable)->modify('last day of'.$ym)->format('d');
		$ts = mktime(0, 0, 0, $month, 1, $year);
		$week = date('w', $ts);

		$days = [];
		//1でlastに+1しておく
		for($i = 1; $i < $last+1; $i++){
			$tmp1 = $ym.'-'.str_pad($i, 2, '0', STR_PAD_LEFT);
			$tmp2 = ($week+$i-1)%7;
			$w = '';
			if($tmp2 == 0){
				$w = 'red';
			} else if($tmp2 == 6){
				$w = 'blue';
			}
			foreach($list as $v){
				if($tmp1 == $v){
					$w = 'red';
					break;
				}
			}
			$days[] = ['day'=>$tmp1, 'week'=>$w];
		}
		return ['week'=>$week, 'days'=>$days];
	}

	function day_cut($date){
		$dt = new DateTime($date);
		return (int)$dt->format('d');
	}
	


?>