<?php 
	
	$numberOfChunkInEachQuery = 250; #The number of row to be insertted in one single statement, if the database returns error, try reduce this number.
	ini_set('memory_limit', '1000M');  #The ammount of memory that is allowed to use, this is the largest ammount of memory I used
	//set_time_limit(1200); 
	try {
		$jsonsToImports = (glob('*.{json}', GLOB_BRACE));
		if (count($jsonsToImports)<1){
			throw new Exception('JSON files not found');
		}
		print "\r\nFound Following JSON Files";
		print_r($jsonsToImports);
		#change the SQL Server setting accodingly: IP adreess/Host Name,User name, password, database
		$link =  mysqli_connect( 'mysql', 'username', 'password', 'database name' );
		if (!$link){
			throw new Exception('MySQL connection failed');
		}
		
		$sql = [];
		$link->query('SET autocommit=0');
		$link ->begin_transaction();
		
		foreach($jsonsToImports as $jsonToInsert){
			issetJson($jsonToInsert,$numberOfChunkInEachQuery);
			unset( $jsonToInsert);
		}
		$link->query("SET FOREIGN_KEY_CHECKS=1");
		if (!in_array(false,$sql)){
			$link->commit();
			print "\r\ncommitted, operation succeed";
			
			
			} else {
			throw new Exception('Query Failed, Insert  will now rollback');
			
			
			
		} 
		} catch (Exception $e) {
		
		print $e->getMessage();
		
		$link->rollback();
		
		} finally {
		$link->close();
		die();
		exit();
		
	}
	
	
	function issetJson($jsonToInsert,$numberOfChunkInEachQuery ){
		global $link,$sql;
		print "\r\nBegin Insert {$jsonToInsert}...";
		
		$json = null;
		$json = json_decode(file_get_contents($jsonToInsert),true);
		
		
		
		if ($json == false){
			throw new Exception('JSON loading Failed, Perhaps the JSON file is incomplete');
		}
		
		$tableName = $json['tablename'];
		$datatype = $json['datatype'];
		$columnSize = $json['columnSize'];
		$nullable = $json['nullable'];
		$trueRowCount = count($json['rows']);
		
		
		
		
		
		//CREATE TABLE
		
		$createTableQuery = "CREATE TABLE IF NOT EXISTS {$tableName}(";
		$colIndex= 0;
		foreach($json['columns'] as $colume){
			$createTableQuery.= $colume;
			
			switch ($datatype[$colIndex]){
				case "bit":
				case "varchar":
				$createTableQuery.=" {$datatype[$colIndex]}({$columnSize[$colIndex]})";
				
				break;
				case "float":
				$createTableQuery.=" DOUBLE";
				break;
				case "int identity":
				$createTableQuery.=" INT";
				break;
				case "smalldatetime":
				$createTableQuery.=" DATETIME";
				break;
				default;
				$createTableQuery.=" {$datatype[$colIndex]}";
				
			}
			if (!$nullable[$colIndex]){
				
				$createTableQuery.= " NOT NULL ";
			}
			
			
			$colIndex++;
			if ($colIndex<count($columnSize)){
				$createTableQuery.=", ";
			}
			
			
		}
		$createTableQuery.=")";
		
		
		$link->query($createTableQuery);
		//END CREATING TABLE
		
		
		
		$link->query("SET FOREIGN_KEY_CHECKS=0");
		
		
		$tempjson =[];
		
		foreach($json['rows'] as $row){
			$tempjson[] = array_combine($json['columns'],$row);		
		}
		
		//$sql[] = $link->query("delete from {$tableName}");
		
		$json=$tempjson;
		
		unset ($tempjson);
		$json = array_chunk($json, $numberOfChunkInEachQuery);
		
		
		$rowCount = 0;
		foreach ($json as $jsonchunck){
			$query=  "REPLACE INTO {$tableName} (".implode(', ',array_keys($jsonchunck[0])).")";
			$query .= "values" ;
			
			foreach ($jsonchunck as $row){
				
				$query.= "(";
				$i=0;
				foreach ($row as $key=>$value){
					
					
					$value = str_replace('\r\n','#_NEWLINE_#',$value);
					$value = mysqli_real_escape_string($link,($value));
					$value = str_replace('#_NEWLINE_#','\r\n',$value);
					
					if (is_null( $value) || strpos($datatype[$i],"date")!==false && empty($value))
					{					
						$query.='NULL,';
						
					}
					else 
					if ($datatype[$i]=="bit"){
						$query.= ($value == true?1:0).', ';
					}
					else
					
					if ( $datatype[$i]=="float" || strpos($datatype[$i],"int")!==false){
						
						$query.= floatval($value).',';
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
			$link->query($query);
			$sql[] = ($link->affected_rows>0)?true:false;
			if (end($sql)==true){
				$rowCount++;
				print "\r\n".(($rowCount*$numberOfChunkInEachQuery)>$trueRowCount?$trueRowCount:($rowCount*$numberOfChunkInEachQuery)) .'/'.$trueRowCount .'  '. round(($rowCount*$numberOfChunkInEachQuery)/$trueRowCount*100,2)."%";
				//print $query;
				//return true;
				} else {
				
				throw new Exception("\r\n".$link->error."\r\n{$query}");
				return false;
			}
			
			unset($query);
			$jsonchunck = null;
		}
		$json = null;
		//return true;
		
	}
?>	