<?php 
	#change the SQL Server setting accodingly: IP adreess/Host Name,User name, password, database
	$link =  mysqli_connect( '127.0.0.1', 'USERNAME', 'PASSWORD', 'DATABASE' );
	$numberOfChunkInEachQuery = 3000; #The number of row to be insertted in one single statement, if the database returns error, try reduce this number.
	ini_set('memory_limit', '6000M');  #The ammount of memory that is allowed to use, this is the largest ammount of memory I used
	set_time_limit(1200); 
	
	$json = file_get_contents('import.json');
	$json = json_decode($json,true);
	
	$tableName = $json['tablename'];
	$datatype = $json['datatype'];
	$trueRowCount = count($json['rows']);
	
	$sql = [];
	
	
	mysqli_begin_transaction($link, MYSQLI_TRANS_START_READ_WRITE);
	
	$sql[] = mysqli_query($link,"SET FOREIGN_KEY_CHECKS=0");
	
	
	$tempjson =[];
	
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
				if ($datatype[$i]=="bit"){
					$query.= ($value===true?1:0).', ';
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
			$rowCount++
			print ($rowCount*$numberOfChunkInEachQuery) .'/'.$trueRowCount .'  '. round(($rowCount*$numberOfChunkInEachQuery)/$trueRowCount*100,2)."% \r\n";
			
			} else {
			print mysqli_error($link);
			
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