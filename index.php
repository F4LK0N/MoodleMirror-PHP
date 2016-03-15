<?php
set_time_limit(60*30);
define("USER_AGENT", "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0");
define("COOKIEFILE", "CookieUnisinos");
define("MAINFOLDER", "E:/Unisinos/Jogos Digitais/Moodle");
define("USERNAME", "");
define("PASSWORD", "");
$materialArray=array();
//######################################################################################################################
// FUNÇÕES
//######################################################################################################################
function convertTo_fileName($fileName){
	
	//NORMALIZE
	$fileName=str_replace("?", "", $fileName);
	$fileName=str_replace("\\", ".", $fileName);
	$fileName=str_replace("/", ".", $fileName);
	$fileName=str_replace(";", ".", $fileName);
	$fileName=str_replace(":", "-", $fileName);
	$fileName=str_replace("|", "-", $fileName);
	$fileName=str_replace("-", " - ", $fileName);
	$fileName=str_replace("_", " ", $fileName);
	while(false!==strpos($fileName, "  ")){ $fileName=str_replace("  ", " ", $fileName); }
	$fileName=trim($fileName);
	$fileName=trim($fileName, ".");
	
	//CAPITALIZE
	$fileName=ucwords($fileName);
	$fileName=str_replace(" A ", " a ", $fileName);
	$fileName=str_replace(" E ", " e ", $fileName);
	$fileName=str_replace(" O ", " o ", $fileName);
	$fileName=str_replace(" Da ", " da ", $fileName);
	$fileName=str_replace(" De ", " de ", $fileName);
	$fileName=str_replace(" Do ", " do ", $fileName);
	$fileName=str_replace(" Na ", " na ", $fileName);
	$fileName=str_replace(" No ", " no ", $fileName);
	$fileName=str_replace(" Em ", " em ", $fileName);
	$fileName=str_replace(" Um ", " um ", $fileName);
	$fileName=str_replace(" Uma ", " uma ", $fileName);
	$fileName=str_replace(" Das ", " das ", $fileName);
	$fileName=str_replace(" Des ", " des ", $fileName);
	$fileName=str_replace(" Dos ", " dos ", $fileName);
	$fileName=str_replace(" Nas ", " nas ", $fileName);
	$fileName=str_replace(" Nos ", " nos ", $fileName);
	
	//LINGUAGE SPECIAL CASES
	$fileName=str_replace(" - Feira", "-Feira", $fileName);
	$fileName=str_replace(" - FEIRA", "-FEIRA", $fileName);
	
	return $fileName;
}
//######################################################################################################################
function convertTo_uppercase($fileName){
	
	$fileName=utf8_encode($fileName);
	
	$fileName=strtoupper($fileName);
	$fileName=str_replace(
		array("á","à","â","ã", "é","è","ê", "í","ì","î", "ó","ò","ô","õ", "ú","ù","û", "ç"),
		array("Á","À","Â","Ã", "É","È","Ê", "Í","Ì","Î", "Ó","Ò","Ô","Õ", "Ú","Ù","Û", "Ç"),
		$fileName
	);
	
	$fileName=utf8_decode($fileName);
	
	return $fileName;
}
//######################################################################################################################
function curlGet($url, $utf8_decode=true){
	
	usleep(100000); //100 miliseconds
	
	$curl=curl_init($url);
	curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIEFILE);
	curl_setopt($curl, CURLOPT_COOKIEJAR, COOKIEFILE);
	curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$page=curl_exec($curl);
	curl_close($curl);
	
	if($utf8_decode){ $page=utf8_decode($page); }
	
	return $page;
}
//######################################################################################################################
function curlDownload($curso, &$material){
	
	//### RESOURCE ###
	if(strpos($material['link'], "/mod/resource/view.php")){
		
		//PDF
		if(is_file(MAINFOLDER."/$curso/".$material['nome'])){ return null; }
		//7Z
		if(is_file(MAINFOLDER."/$curso/".substr($material['nome'], 0, -4).".7z")){ 
			$material['nome']=substr($material['nome'], 0, -4).".7z";
			return null; }
		
		print
		"<small style='color:#0B0'>DOWNLOADING...</small> ";
		
		$page=curlGet($material['link']);
		$link=preg_split("(\<a href=(\"|\'))", $page, 2);
		$link=preg_split("((\"|\')>)", $link[1], 2);   $link=$link[0];
		$link=str_replace("?forcedownload=1", "", $link);
		
		
		//### PDF ###
		if(".pdf"===substr($link, -4)){
			$file=curlGet($link, false);
			if(!$file){ print
				"<small style='color:#B00'>DOWNLOAD ERROR!</small> "; }
			else{
				file_put_contents(MAINFOLDER."/$curso/".$material['nome'], $file); }
			
			return null;
		}
		
		//### 7Z ###
		if(".7z"===substr($link, -3)){
			
			$material['nome']=substr($material['nome'], 0, -4).".7z";
			
			$file=curlGet($link, false);
			if(!$file){ print
				"<small style='color:#B00'>DOWNLOAD ERROR!</small> "; }
			else{
				file_put_contents(MAINFOLDER."/$curso/".$material['nome'], $file); }
			
			return null;
		}
		
		//### PLUGINFILE ###
		if(strpos($page, "http://www.moodle.unisinos.br/pluginfile.php/")){
			
			$link=explode("http://www.moodle.unisinos.br/pluginfile.php/", $page, 2);
			$link=preg_split("(\"|\')", "http://www.moodle.unisinos.br/pluginfile.php/".$link[1], 2);   $link=$link[0];
			$link=str_replace("?forcedownload=1", "", $link);
			
			//### PLUGINFILE - PDF ###
			if(".pdf"===substr($link, -4)){
				$file=curlGet($link, false);
				if(!$file){ print
					"<small style='color:#B00'>DOWNLOAD ERROR!</small> "; }
				else{
					file_put_contents(MAINFOLDER."/$curso/".$material['nome'], $file); }
				
				return null;
			}
		}
		
		//### UNKNOW ###
		print
		"<small style='color:#B00'>EXTENSÃO DESCONHECIDA!</small> "; 
		$material['link'].="<KEEP ORIGINAL LINK!>";
		var_dump($page); die;
	}
	
	//### ASSIGN ###
	elseif(strpos($material['link'], "/mod/assign/view.php")){
		
		$material['nome']=substr($material['nome'], 0, -4).".htm";
		
		if(is_file(MAINFOLDER."/$curso/".$material['nome'])){ return null; }
		
		print
		"<small style='color:#0B0'>DOWNLOADING...</small> ";
		
		$file=curlGet($material['link'], false);
		if(!$file){ print
			"<small style='color:#B00'>DOWNLOAD ERROR!</small> "; }
		else{
			file_put_contents(MAINFOLDER."/$curso/".$material['nome'], $file); }
	}
	
	//### URL ###
	elseif(strpos($material['link'], "/mod/url/view.php")){
		
		if(is_file(MAINFOLDER."/$curso/".$material['nome'])){ 
			$link=file_get_contents(MAINFOLDER."/$curso/".$material['nome']); }
		
		else{ print
			"<small style='color:#0B0'>ACESSING...</small> ";
			
			$link=curlGet($material['link']);
			$link=preg_split("(\<a href=(\"|\'))", $link, 2);
			$link=preg_split("((\"|\')>)", $link[1], 2);   $link=$link[0];
			
			file_put_contents(MAINFOLDER."/$curso/".$material['nome'], $link);
		}
		
		$material['nome']=$link;
	}
	
	//### FORUM ###
	elseif(strpos($material['link'], "/mod/forum/view.php")){
		$material['link'].="<KEEP ORIGINAL LINK!>";
	}
	
	//### UNKNOW ###
	else{ print
		"<small style='color:#B00'>MATERIAL DESCONHECIDO!</small> "; 
		$material['link'].="<KEEP ORIGINAL LINK!>"; }
}
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################


	


//###########################################################
//### HOME ###
//###########################################################
$pageMoodle=curlGet("http://www.moodle.unisinos.br");

print
"Acessando pagina inicial...<br>";
// "<div style='width:90%; margin:auto; border:5px solid #000; max-height:400px; overflow-y:auto;'>$pageMoodle</div><br>".
//###########################################################



//###########################################################
//### LOGIN ###
//###########################################################
if(strpos($pageMoodle, "<form action=\"http://www.moodle.unisinos.br/login/index.php\" method=\"post\" id=\"login\">")){
	
	sleep(1);
	
	$curlLogin=curl_init("http://www.moodle.unisinos.br/login/index.php");
	curl_setopt($curlLogin, CURLOPT_COOKIEFILE, COOKIEFILE);
	curl_setopt($curlLogin, CURLOPT_COOKIEJAR, COOKIEFILE);
	curl_setopt($curlLogin, CURLOPT_USERAGENT, USER_AGENT);
	curl_setopt($curlLogin, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlLogin, CURLOPT_POST, true);
	curl_setopt($curlLogin, CURLOPT_POSTFIELDS, "username=".USERNAME."&password=".PASSWORD);
	$pageLogin=curl_exec($curlLogin);
	
	print
	"Usuario deslogado, efetuando login...".
	//"<div style='width:90%; margin:auto; border:5px solid #000; max-height:400px; overflow-y:auto;'>$pageLogin</div><br>".
	"<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
	die;
}
//###########################################################



//###########################################################
//### CURSOS - LIST ###
//###########################################################
print
"Obtendo lista de cursos...<br>";

if(!strpos($pageMoodle, "href=\"http://www.moodle.unisinos.br/course/view.php?id=")){
	print"Cursos não encontrados!"; die; }

//GET LIST
$cursosList=array();
$posInit=0; while($posInit=strpos($pageMoodle, "href=\"http://www.moodle.unisinos.br/course/view.php?id=", $posInit)){
	$posEnd=strpos($pageMoodle, "</a>", $posInit);
	$cursoTemp=substr($pageMoodle, $posInit, ($posEnd-$posInit));
	$posInit=$posEnd;
	$cursosList[]=$cursoTemp;
}


//SPLIT VALUES
foreach($cursosList as $key => $curso){
	
	//ID
	$posI=strpos($curso, "?id=")+4;
	$posF=strpos($curso, "\">");
	$tempArray['id']=substr($curso, $posI, ($posF-$posI));
	
	//NAME FULL
	$posI=$posF+2;
	$tempArray['nameFull']=substr($curso, $posI);
	
	//NAME FOLDER
	$tempArray['nameFolder']=substr($tempArray['nameFull'], 0, strpos($tempArray['nameFull'], " - GR", 7));
	$tempArray['nameFolder']=convertTo_fileName($tempArray['nameFolder']);
	
	
	$cursosList[$key]=$tempArray;
	unset($tempArray);
	
	print
	"<small>Curso: ".$cursosList[$key]['nameFolder']."<br></small>";
}
print
"<br>";
//###########################################################



//###########################################################
//### ESTRUTURA DE PASTAS ###
//###########################################################
print
"Criando estrutura de pastas...<br>".
"Criando index principal...<br>";
@mkdir(MAINFOLDER);
foreach($cursosList as $key => $curso){
	@mkdir(MAINFOLDER."/".$curso['nameFolder']);
	$pageMoodle=str_replace("href=\"http://www.moodle.unisinos.br/course/view.php?id=".$curso['id'], "href=\"".$curso['nameFolder']."/00-00-index.htm", $pageMoodle);
}
file_put_contents(MAINFOLDER."/00-00-index.htm", utf8_encode($pageMoodle));
print
"<br>";
//###########################################################


	


//###########################################################
//### CURSO ###
//###########################################################
foreach($cursosList as $cursoIndex => $curso){ print
	"Acessando curso:<br>".
	"<b>".$curso['nameFolder']."</b><br>";
	$pageCurso=curlGet("http://www.moodle.unisinos.br/course/view.php?id=".$curso['id']);
	
	
	//### TOPICOS ###
	// Inicio: (class="sectionname">)
	// Final:  (<aside id=)
	if(!preg_split("(class\=(\"|\')sectionname(\"|\')\>)", $pageCurso)){ print
		"Topicos não encontrados!"; }
	
	else{
		$topicosArray=preg_split("(\<aside id\=)", $pageCurso);
		$topicosArray=preg_split("(class\=(\"|\')sectionname(\"|\')\>)", $topicosArray[0]);
		unset($topicosArray[0]);
		
		foreach($topicosArray as $topicoIndex => $topicoContent){
			
			//### TOPICO ###
			$topico=explode("</h3>", $topicoContent, 2);   $topico=$topico[0];
			$topico=convertTo_fileName($topico);
			$topico=convertTo_uppercase($topico);
			$topico=str_pad($topicoIndex, 2, "0", STR_PAD_LEFT)."-00 - ### ### ### ### $topico ### ### ### ###";
			file_put_contents(MAINFOLDER."/".$curso['nameFolder']."/$topico", "");
			print"$topico<br>";
			
			//### MATERIAIS ###
			// Inicio: (<ul class="section img-text">)
			// Final:  (</ul>)
			$materiaisArray=preg_split("(\<ul class\=(\"|\')section img\-text(\"|\')\>)", $topicoContent);   unset($topicoContent);
			$materiaisArray=explode("</ul>", $materiaisArray[1]);   $materiaisArray=$materiaisArray[0];
			$materiaisArray=explode("<a", $materiaisArray);   unset($materiaisArray[0]);
			
			foreach($materiaisArray as $materialIndex => $materialRaw){
				
				//### MATERIAL ###
				$material=array();
				
				//ID
				$material['id']=explode("?id=", $materialRaw);
				$material['id']=preg_split("(\"|\&)", $material['id'][1], 2);   $material['id']=$material['id'][0];
				
				//EXTENSION
				if(strpos($materialRaw, "f/pdf-24\"")){
					$material['ext']=".pdf"; }
				else{
					$material['ext']=".txt"; }
				
				//NOME
				$material['nome']=preg_split("(\<span class\=(\"|\')instancename(\"|\')\>)", $materialRaw, 2);
				$material['nome']=explode("<", $material['nome'][1]);   $material['nome']=$material['nome'][0];
				$material['nome']=convertTo_fileName($material['nome']).$material['ext'];
				$material['nome']=str_pad($topicoIndex, 2, "0", STR_PAD_LEFT)."-".str_pad($materialIndex, 2, "0", STR_PAD_LEFT)." - ".$material['nome'];
				
				//LINK
				$material['link']=preg_split("((\"|\')http)", $materialRaw, 2);
				$material['link']=explode($material['id'], $material['link'][1], 2);   unset($material['link'][1]);
				$material['link']="http".$material['link'][0].$material['id'];
				
				unset($materialRaw);
				
				//DOWNLOAD
				curlDownload($curso['nameFolder'], $material);
				
				//LINK CHANGE
				$pageCurso=str_replace($material['link'], $material['nome'], $pageCurso);
				
				print"<small>".$material['nome']."</small><br>";
				//### MATERIAL - END ###
			}
			//### MATERIAIS - END ###
		}
	}
	//### TOPICOS - END ###
	
	
	//CURSO PAGE
	file_put_contents(MAINFOLDER."/".$curso['nameFolder']."/00-00-index.htm", utf8_encode($pageCurso));
	
	print
	"<br>";
}
//###########################################################





