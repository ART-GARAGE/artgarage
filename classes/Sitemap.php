<?php
namespace ArtGarage;

class Sitemap {
	
	public static function sitemap($options = array())
	{
		global $modx;
		$startid = isset($options["startid"]) ? $options["startid"] : 0;
		$priority = isset($options["priority"]) ? $options["priority"] : "sitemap_priority";
		$changefreq = isset($options["changefreq"]) ? $options["changefreq"] : "sitemap_changefreq";
		$excludeTemplates = isset($options["excludeTemplates"]) ? $options["excludeTemplates"] : array();
		$excludeTV = isset($options["excludeTV"]) ? $options["excludeTV"] : "sitemap_exclude";
		$xsl = isset($options["xsl"]) ? $options["xsl"] : "";
		$excludeWeblinks = isset($options["excludeWeblinks"]) ? $options["excludeWeblinks"] : 1;
		$seeThruUnpub = (isset($options["seeThruUnpub"]) && $options["seeThruUnpub"] == '0') ? false : true;
		$format       = (isset($options["format"]) && ($options["format"] != 'ror')) ? $options["format"] : 'sp';
		
		if (is_numeric($xsl)) $xsl = $modx->makeUrl($xsl, '', '', 'full');
		$docs = self::getDocs($modx, $startid, $priority, $changefreq, $excludeTV, $seeThruUnpub);
		
		$select = $modx->db->select('id, templatename', '[+prefix+]site_templates');
		while ($query = $modx->db->getRow($select)){
			$allTemplates[$query['id']] = $query['templatename'];
		}
		$remainingTemplates = $allTemplates;
		if (!empty ($excludeTemplates)){
			$excludeTemplates = explode(',', $excludeTemplates);
			foreach ($excludeTemplates as $template){
				$template = trim($template);
				if (is_numeric($template) && isset($remainingTemplates[$template])){
					unset($remainingTemplates[$template]);
				}elseif (trim($template) && in_array($template, $remainingTemplates)){
					unset($remainingTemplates[array_search($template, $remainingTemplates)]);
				}
			}
		}
		$_ = array();
		foreach ($docs as $doc){
			if(!isset($remainingTemplates[$doc['template']])) continue;
			if($doc[$excludeTV])                              continue;
			if($doc[$changefreq]=='exclude')                  continue;
			if(!$doc['published'])                            continue;
			if(!$doc['template'])                             continue;
			if(!$doc['searchable'])                           continue;
			if($excludeWeblinks && $doc['type']=='reference') continue;
			if($doc['id']==$modx->documentIdentifier)         continue;
			$_[] = $doc;
		}
		$docs = $_;
		unset ($_, $allTemplates, $excludeTemplates);
		$output = array();
		switch ($format){
			case 'ulli':
				$output[] = '<ul class="sitemap">';
				foreach ($docs as $doc){
					$s  = '  <li class="sitemap">';
					$s .= '<a href="'.$doc['url'].'" class="sitemap">' . $doc['pagetitle'] . '</a>';
					$s .= '</li>';
					$output[] = $s;
				}
				$output[] = '</ul>';
				break;
			case 'txt':
				foreach ($docs as $doc){
					$output[] = $doc['url'];
				}
				break;
			case 'ror':
			default:
				$output[] = '<?xml version="1.0" encoding="'.$modx->config["modx_charset"].'"?>';
				if ($xsl) $output[] ='<?xml-stylesheet type="text/xsl" href="'.$xsl.'"?>';
				
				$output[] ='<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

				foreach ($docs as $doc) {
					$output[] = '    <url>';
					$output[] = '        <loc>'.htmlentities($doc['url']).'</loc>';
					if($doc['editedon'])
						$output[] = '        <lastmod>'.date('Y-m-d', $doc['editedon']).'</lastmod>';
					$output[] = '        <priority>'.$doc[$priority].'</priority>';
					$output[] = '        <changefreq>'.$doc[$changefreq].'</changefreq>';
					$output[] = '    </url>';
				}
				$output[] = '</urlset>';

		}
		return join("\n",$output);
	}
	
	private static function getTV($modx, $docid, $doctv){
		while ($pid = $modx->getDocument($docid, 'parent')){
			$tv = $modx->getTemplateVar($doctv,'*',$docid);
			if (($tv['value'] && substr($tv['value'],0,8) != '@INHERIT') || !$tv['value']){
				$output = $tv['value'];
				break;
			}else{
				$output = trim(substr($tv['value'],8));
			}
			$docid = $pid['parent'];
		}
		return $output;
	}
	
	private static function getDocs($modx, $startid, $priority, $changefreq, $excludeTV, $seeThruUnpub){
		$fields = "id,editedon,template,published,searchable,pagetitle,type,isfolder,parent,publishedon,content LIKE '%<img%' as hasImage";
		if ($seeThruUnpub) $docs = $modx->getAllChildren($startid, 'menuindex', 'asc', $fields);
		else               $docs = $modx->getActiveChildren($startid, 'menuindex', 'asc', $fields);

		$rs = $modx->db->select('name','[+prefix+]site_tmplvars',sprintf("name='%s'",$modx->db->escape($priority)));
		$priority_exists = $modx->db->getRecordCount($rs) ? 1 : 0;
		$rs = $modx->db->select('name','[+prefix+]site_tmplvars',sprintf("name='%s'",$modx->db->escape($changefreq)));
		$changefreq_exists = $modx->db->getRecordCount($rs) ? 1 : 0;
		$rs = $modx->db->select('name','[+prefix+]site_tmplvars',sprintf("name='%s'",$modx->db->escape($excludeTV)));
		$excludeTV_exists  = $modx->db->getRecordCount($rs) ? 1 : 0;
		
		foreach ($docs as $i => $doc){
			$id = $doc['id'];
			if(!$doc['editedon']) $doc['editedon'] = $doc['publishedon'];
			if($id==$modx->config['site_start']) $docs[$i]['url'] = $modx->config['site_url'];
			else                                 $docs[$i]['url'] = trim($modx->makeUrl($id,'','','full'));
			
			$date_diff = round(($_SERVER['REQUEST_TIME']-(int)$doc['editedon'])/86400);
			
			if($priority_exists)                     $docs[$i][$priority] = self::getTV($modx, $id, $priority);
			elseif($id==$modx->config['site_start']) $docs[$i][$priority] = '1.0';
			elseif($date_diff<7)                     $docs[$i][$priority] = '0.9';
			elseif($date_diff<14)                    $docs[$i][$priority] = '0.8';
			elseif($doc['parent']==0)                $docs[$i][$priority] = '0.6';
			elseif($doc['isfolder'])                 $docs[$i][$priority] = '0.4';
			elseif(1000<$date_diff) {
				if($doc['hasImage'])                 $docs[$i][$priority] = '0.4';
				else                                 $docs[$i][$priority] = '0.3';
			}
			else                                     $docs[$i][$priority] = '0.5';
			
			if($changefreq_exists)                   $docs[$i][$changefreq] = self::getTV($modx, $id, $changefreq);
			elseif($id==$modx->config['site_start']) $docs[$i][$changefreq] = 'always';
			elseif($doc['isfolder'])                 $docs[$i][$changefreq] = 'always';
			elseif(365<$date_diff)                   $docs[$i][$changefreq] = 'never';
			elseif(180<$date_diff)                   $docs[$i][$changefreq] = 'yearly';
			elseif(60<$date_diff)                    $docs[$i][$changefreq] = 'monthly';
			elseif(14<$date_diff)                    $docs[$i][$changefreq] = 'weekly';
			elseif($date_diff)                       $docs[$i][$changefreq] = 'daily';
			else                                     $docs[$i][$changefreq] = 'never';
			
			if($excludeTV_exists) $docs[$i][$excludeTV] = self::getTV($modx, $id, $excludeTV);
			else                  $docs[$i][$excludeTV] = false;
			$docs = array_merge($docs, self::getDocs($modx, $id, $priority, $changefreq, $excludeTV, $seeThruUnpub));
		}
		return $docs;
	}
}