<?php
namespace App\Http\Controllers;

use DOMDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;

class TablesController extends Controller {

	public function xml($table,$field='',$value='')
	{
		if($field!='' && $value!='')
		{
			$rows = DB::connection('conf')->select('select * from t_'.$table.' where '.$field.'='.$value);
		}
		else
		{
			if($table=='shop')	//shop 特殊处理
				$rows = DB::connection('conf')->select('select * from t_'.$table.' where platform=0');
			else
				$rows = DB::connection('conf')->select('select * from t_'.$table);
		}
		
		$dom=new DOMDocument('1.0','utf-8');
		
		$dom->formatOutput = true;
		
		$root=$dom->createElement('xml');//创建一个节点
		$dom->appendChild($root); //在指定元素节点的最后一个子节点之后添加节点

		foreach($rows as $key=>$value) {
			if(is_object($value))
				$tmp = (array)$value;
			else
				$tmp = $value;
				
			$item=$dom->createElement('item');
			$root->appendChild($item);
			foreach($tmp as $key1=>$value1) {
				$attr = $dom->createAttribute($key1);
				$attr->value = $value1;
				$item->appendChild($attr);
			}
		}
		
		$content = $dom->saveXML();
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/xml');
		$response->header('Table-Name',$table);
		return $response;
	}
	
	public function json($table,$field='',$value='',$value1='')
	{
		if($field!='' && $value!='' && $value1=='')
		{		
			if($table=='shop' && $field=='platform' && $value=='999')
				$rows = DB::connection('conf')->select('select * from t_'.$table);
            else
            	$rows = DB::connection('conf')->select('select * from t_'.$table.' where '.$field.'='.$value);
                        
		}
		else if($field!='' && $value!=''&&$value1!='')
		{
         	if($table=="gift_v1_6_7"){
				$rows = DB::connection('conf')->select('select * from t_'.$table.' where ('.$field." like '%".$value."%' or ".$field.'='.$value1.") and is_show=1");
             }                 
        }
        else
		{
			if($table=='shop') //shop 特殊处理
				$rows = DB::connection('conf')->select('select * from t_'.$table.' where platform=0');
			 else if($table=='activity_v1_6_5')
                            $rows = DB::connection('conf')->select('select *,now() nowtime from t_'.$table);
                        else
				$rows = DB::connection('conf')->select('select * from t_'.$table);
		}
		
		$content = json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	        $content=urldecode($content);
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
                //$response->header('charset','utf-8');
		//$response->header('Charset','utf-8');
		$response->header('Table-Name',$table);
		return $response;
	}
	
	public function csv($table,$field='',$value='')
	{
		if($field!='' && $value!='')
		{
			$rows = DB::connection('conf')->select('select * from t_'.$table.' where '.$field.'='.$value);
		}
		else
		{
			if($table=='shop') //shop 特殊处理
				$rows = DB::connection('conf')->select('select * from t_'.$table.' where platform=0');
			else
				$rows = DB::connection('conf')->select('select * from t_'.$table);
		}
		
		$data = array();
		foreach($rows as $key=>$value) {
			if(is_object($value))
				$data[$key] = (array)$value;
			else
				$data[$key] = $value;
		}
		
		//print_r($array);
		//$rows = DB::connection('conf')->table('t_shop')->get();
		//$rows = json_decode(json_encode($rows),TRUE);
		
		return Excel::create($table, function($excel) use ($data) {
			$excel->sheet('sheet1', function($sheet) use ($data) {
				$sheet->fromArray($data, null, 'A1', true);
			});
		})->download('csv');
	}

	public function xls($table,$field='',$value='')
	{
		if($field!='' && $value!='')
		{
			$rows = DB::connection('conf')->select('select * from t_'.$table.' where '.$field.'='.$value);
		}
		else
		{
			if($table=='shop') //shop 特殊处理
				$rows = DB::connection('conf')->select('select * from t_'.$table.' where platform=0');
			else
				$rows = DB::connection('conf')->select('select * from t_'.$table);
		}
	
		$data = array();
		foreach($rows as $key=>$value) {
			if(is_object($value))
				$data[$key] = (array)$value;
			else
				$data[$key] = $value;
		}
	
		return Excel::create($table, function($excel) use ($data) {
			$excel->sheet('sheet1', function($sheet) use ($data) {
				$sheet->fromArray($data, null, 'A1', true);
			});
		})->download('xls');
	}
	
}