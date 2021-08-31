<?php 
	$link =  mysqli_connect( '127.0.0.1', 'kf4', 'Kf6655caton!', 'test' );
	ini_set('memory_limit', '6000M');
	set_time_limit(1200); 
	
	$json = file_get_contents('import.json');
	$json = json_decode($json,true);
	
	$tableName = $json['tablename'];
	$datatype = $json['datatype'];
	
	$sql = [];
	/* print_r($json['columns']);
	print_r($json['datatype']);
	print $tableName."<br>"; */
	
	
	
	
	mysqli_begin_transaction($link, MYSQLI_TRANS_START_READ_WRITE);
	
	$sql[] = mysqli_query($link,"SET FOREIGN_KEY_CHECKS=0");
	
	$numberOfChunkInEachQuery = 3000;
	
	$tempjson =[];
	
	//array_combine($json['columns'],$json['rows']);
	
	
	
	foreach($json['rows'] as $row){
		$tempjson[] = array_combine($json['columns'],$row);		
	}
	
	
	
	$json=$tempjson;
	
	unset ($tempjson);
	$json = array_chunk($json, $numberOfChunkInEachQuery);
	
	
	
	foreach ($json as $jsonchunck){
		$query=  "replace into {$tableName} (".implode(', ',array_keys($jsonchunck[0])).")";
		$query .= "values" ;
		
		foreach ($jsonchunck as $row){
			
			$query.= "(";
			$i=0;
			foreach ($row as $key=>$value){
				$value = str_replace('\r\n','#_NEWLINE_#',$value);
				//$value = mysqli_real_escape_string($link,($value));
				$value = mysqli_real_escape_string($link,($value));
				 $value = str_replace('#_NEWLINE_#','\r\n',$value);
				
				if ( $value ==null  )
				{
					if(!empty($value)){
						$query.="0,";
						} else {
						$query.='null,';
					}
				}
				else 
				if ($value ===true || $value===false){
					$query.= (int)$value.', ';
				}
				else
				
				if (is_numeric($value) && ($datatype[$i]=="float" || strpos($datatype[$i],"int")!==false)){
					
					$query.= $value.',';
				} 
				else 
				
				{
					$query.= "\"{$value}\",";
				}
				$i++;
			}
			$query = substr($query,0,-1); //remove Last comma
			
			$query.="),";
			
			
			
		}
		$query = substr($query,0,-1); //remove Last comma
		$sql[] = mysqli_query($link,$query);
		if (end($sql)==true){
			//print $query;
			print $query;
			} else {
			print mysqli_error($link);
			//print "<br>".$query;
			die();
		}
		unset($query);
		flush();
	}
	$sql[] = mysqli_query($link,"SET FOREIGN_KEY_CHECKS=1");
	
	if (!in_array(false,$sql)){
		mysqli_commit($link);
		print "commit";
		
		
		} else {
		mysqli_rollback($link);
		print "fail";
		
	}
	mysqli_close($link);
	die();
	exit();
?>