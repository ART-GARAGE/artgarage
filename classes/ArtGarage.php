<?php
namespace ArtGarage;

class ArtGarage {
	
	
	public static function addChunks(\DocumentParser $modx, $path)
	{
		if(!is_dir($path)):
			echo "Is not dir".$path;
			exit();
		endif;
		foreach (glob($path."/*.tpl") as $chunkFile):
			if(is_file($chunkFile) && is_readable($chunkFile)):
				$chunk = pathinfo($chunkFile, PATHINFO_FILENAME);
				$code = (string)file_get_contents($chunkFile);
				$modx->addChunk($chunk, $code);
			endif;
		endforeach;
	}
	
	public static function addSnippets(\DocumentParser $modx)
	{
		// Создать и назначить всем шаблонам параметр TV - ogimage. Тип image
		// Зависимость: phpthumb snippet
		// Пример: [[#OgImage]]
		// Вывод: 
		/*
		<meta property="og:image" content="http://exemple.com/assets/cache/images/modx-logo-537x240-7e5.jpeg" />
		<meta property="og:image:width" content="537" />
		<meta property="og:image:height" content="240" />
		<meta property="og:image:type" content="image/jpeg" />
		<meta property="og:image" content="http://exemple.com/assets/cache/images/modx-logo-400x400-53a.jpeg" />
		<meta property="og:image:width" content="400" />
		<meta property="og:image:height" content="400" />
		<meta property="og:image:type" content="image/jpeg" />
		*/
		$modx->addSnippet('OgImage', '\ArtGarage\ArtGarage::headOgImage');
		
		// Пример: [!#GetFileContent? &input=`assets/templates/mytemplate/css/main.css` &type=`css`!]
		// Вывод:  <style>/*Ваш файл стилей*/</style>
		// Пример: [!#GetFileContent? &input=`assets/templates/mytemplate/js/main.js` &type=`js`!]
		// Вывод:  <script type="text/javascript">/*Ваш файл cкрипта*/</style>
		// Пример: <img src="[[#GetFileContent? &input=`assets/templates/mytemplate/image/logo.png` &type=`base64`]]" alt="">
		// Вывод:  <img src="data:image/png;base64,.........." alt="">
		$modx->addSnippet('GetFileContent', '\ArtGarage\ArtGarage::getFileContent');
		
		// Время последнего изменения файла
		// Пример: <script src="[!#GetFileAndTime? &input=`assets/templates/mytemplate/js/main.js`!]" type="text/javascript"></script>
		// Вывод:  <script src="assets/templates/mytemplate/js/main.js?1509867763" type="text/javascript"></script>
		$modx->addSnippet('GetFileAndTime', '\ArtGarage\ArtGarage::getFileAndTime');
		
		// Пример: [!#GetMailEncode? &input=`вебмастер@вебмастер.рф`!]
		$modx->addSnippet('GetMailEncode', '\ArtGarage\ArtGarage::getMailEncode');
		
		// Полный порт сниппета sitemap. Чтобы не засорять сниппетами перенёс в класс.
		// Зависимость: TV параметры 
			/*
				name:			sitemap_changefreq
				title:			Период обновления
				description:	Для карты сайта
				category:		SEO
				type:			dropdown list
				elements:		always||hourly||daily||weekly||monthly||yearly||never
				default:		weekly
			*/
			/*
				name:			sitemap_exclude
				title:			Отображение в sitemap
				description:	Для карты сайта
				category:		SEO
				type:			checkbox
				elements:		Скрыть==1
				default:		0
			*/
			/*
				name:			sitemap_priority
				title:			Приоритет страницы
				description:	Для карты сайта
				category:		SEO
				type:			dropdown list
				elements:		0.1||0.2||0.3||0.4||0.5||0.6||0.7||0.8||0.9||1
				default:		0.5
			*/
		$modx->addSnippet('Sitemap', '\ArtGarage\Sitemap::sitemap');
		$modx->addSnippet('GetYouTubeFrame', '\ArtGarage\ArtGarage::GetYouTubeFrame');
		$modx->addSnippet('hsc', '\ArtGarage\ArtGarage::hsc');
		$modx->addSnippet('LastYear', '\ArtGarage\ArtGarage::LastYear');
		$modx->addSnippet("GetPhones", '\ArtGarage\ArtGarage::GetPhones');
	}
	
	public static function headOgImage()
	{
		global $modx;
		$imageOg = $modx->documentObject['ogimage'][1];
		$output = "";
		if(is_file(MODX_BASE_PATH.$imageOg)):
			$one_input = $modx->runSnippet('phpthumb',
				array(
					'input'=>$imageOg,
					'options'=>'w=537,h=240,zc=C,bg=ffffff,f=jpeg'
				)
			);
			$two_input = $modx->runSnippet('phpthumb',
				array(
					'input'=>$imageOg,
					'options'=>'w=400,h=400,zc=C,bg=ffffff,f=jpeg'
				)
			);
			$one_input_size = getimagesize(MODX_BASE_PATH.$one_input);
			$two_input_size = getimagesize(MODX_BASE_PATH.$two_input);
			$output = "<meta property=\"og:image\" content=\"[(site_url)]".$one_input."\" />\n";
			if($one_input_size):
				$output .= "		<meta property=\"og:image:width\" content=\"".$one_input_size[0]."\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"".$one_input_size[1]."\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"".$one_input_size['mime']."\" />\n";
			endif;
				$output .= "		<meta property=\"og:image\" content=\"[(site_url)]".$two_input."\" />\n";
			if($one_input_size):
				$output .= "		<meta property=\"og:image:width\" content=\"".$two_input_size[0]."\" />\n";
				$output .= "		<meta property=\"og:image:height\" content=\"".$two_input_size[1]."\" />\n";
				$output .= "		<meta property=\"og:image:type\" content=\"".$two_input_size['mime']."\" />\n";
			endif;
		endif;
		return $output;
	}
	
	public static function getFileContent($options = array('input'=>"", 'type' => ""))
	{
		$file = trim($options['input']);
		$type = trim($options['type']);
		$return = "";
		if(is_file(MODX_BASE_PATH . $file)):
			$return = file_get_contents(MODX_BASE_PATH . $file);
			switch($type){
				case "css":
					$return = "<style>" . $return . "</style>";
					break;
				case "js":
					$return = "<script type=\"text/javascript\">" . $return . "</script>";
					break;
				case "base64":
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mime = "data:"  . finfo_file($finfo, MODX_BASE_PATH . $file) . ";base64,";
					finfo_close($finfo);
					$return = $mime . base64_encode($return);
					break;
			}
		endif;
		return $return;
	}
	
	public static function getFileAndTime($options = array('input' => ""))
	{
		$return = trim($options['input']);
		$time = time();
		if(is_file(MODX_BASE_PATH . $return)):
			$time = filemtime(MODX_BASE_PATH . $return);
		endif;
		return $return . "?" . $time;
	}
	
	public static function getMailEncode($options = array('input'=>""))
	{
		$idna = new \ArtGarage\IdnaConvert(array('idn_version'=>2008));
		$return = $idna->encode($options['input']);
		unset($idna);
		return $return;
	}
	
	private static function replace_project_html($matches){
		$res = preg_replace('(\r(?:\n)?)', "\xD6\xD6\xD6\xD6", $matches[2]);
		return $matches[1].'"'.$res.'"';
	}
	
	private static function parseText($tpl,$data,$prefix = '[+',$suffix = '+]') {
		if(!is_array($data))
			return $tpl;
        foreach($data as $k => $v) {
            $tpl = str_replace($prefix.(string)$k.$suffix, (string)$v, $tpl);
        }
        return $tpl;
    }
	
	public static function prepareMinifyHtml(\DocumentParser $modx)
	{
		$str = $modx->documentOutput;
		$re = '/(<meta\s(?:.+)?name=(?:\'|")viewport(?:\'|")(?:.+)?\/?>)/';
		$subst = '$1'."\n".'<meta name="cmsmagazine" content="d8e7426ec72ad3e4ea38b09ebf01284c">';
		$str = preg_replace($re, $subst, $str);
		if($modx->documentObject['minify'][1]==1):
			$re = '/((?:content=)|(?:"description":\s+))(?:"|\')([A-я\S\s\d\D\X\W\w]+)(?:"|\')/mUi';
			$str = preg_replace_callback($re, array(self,'replace_project_html'), $str);
			$str = preg_replace("/(\xD6\xD6\xD6\xD6)/", "\n", preg_replace('|\s+|', ' ', preg_replace('|(\s+)?\n(\s+)?|', '', preg_replace('|<!(--)?(\s+)?(?!\[).*-->|', '', $str))));
		endif;
		$modx->documentOutput = $str;
	}
	
	public static function GetYouTubeFrame($options = array('url'=>""))
	{
		$out = "";
		$re = '/v=(.+)$/';
		preg_match($re, $options['url'], $matches, PREG_OFFSET_CAPTURE, 0);
		if($matches[1]){
			$out = '<div class="embed-responsive embed-responsive-16by9 mt-20 mb-30">
				<iframe src="https://www.youtube.com/embed/'.$matches[1][0].'?rel=0&showinfo=0" frameborder="0" allowfullscreen="" wmode="opaque"></iframe>
			</div>';
		}
		return $out;
	}
	
	public static function hsc($options = array('input' => ""))
	{
		global $modx;
		$value = $options["input"]."";
		return preg_replace('/&amp;(#[0-9]+|[a-z]+);/i', '&$1;', htmlspecialchars($value, ENT_QUOTES, $modx->config['modx_charset']));
	}
	
	public static function LastYear ($options = array("input" => 1970)) {
		$num = (int)$options["input"];
		$text = '';
		$now_date = date("Y");
		$diff = $now_date - $num;
		$diff = intval($diff / 5) * 5;
		$last_num = ($diff % 10);
		if ($diff < 21) {
			switch ($diff) {
				case 0:
					$god_goda_let = 'лет';
					break;
				case 1:
					$god_goda_let = 'год';
					break;
				case 2:
				case 3:
				case 4:
					$god_goda_let = 'года';
					break;
				default:
					$god_goda_let = 'лет';
					break;
			}
		} else {
			if ($last_num == 1) {
				$god_goda_let = 'год';
			}elseif ($last_num == 2 or $last_num == 3 or $last_num == 4) {
				$god_goda_let = 'года';
			}else{
				$god_goda_let = 'лет';
			}
		}
		$total = "$diff $god_goda_let";
		return $total;
	}
	
	public static function GetPhones($options = array('input' => "")) {
		global $modx;
		$jsonObj = json_decode($options['input']."");
		$tpl = isset($options["rowTpl"]) ? $options["rowTpl"] : "<a class=\"link\" href=\"[+link+]\">[+phone+]</a>";
		$outer = isset($options["outerTpl"]) ? $options["outerTpl"] : "[+wrapper+]";
		$rows = "";
		$return = "";
		if(is_object($jsonObj)):
			if(is_array($jsonObj->fieldValue)):
				foreach($jsonObj->fieldValue as $key=>$phone):
					$strphone = (string)$phone->phone;
					$link = "tel:".preg_replace('/(\s+|\(|\)|-)/', '', $strphone);
					$data = array(
						'link' => $link,
						'phone' => $strphone
					);
					$rows .= self::parseText($tpl, $data);
				endforeach;
				$return = self::parseText($outer, array('wrapper'->$rows));
			endif;
		endif;
		return $return.$rows;
	}
}