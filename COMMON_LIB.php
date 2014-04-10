<?php
	//------------------------------------------------------------------
	# Функция получения видов оплат (пример: наличными, кредитом и т.д.)
	function GET_PAYS_CODE($connect)
	{
		$counter = 0;

		$query = "SELECT CODE, NAME FROM PAYMENT";
		$payments = ibase_query($connect, $query) or die(ibase_errmsg());
	
		while ($row[$counter] = ibase_fetch_assoc($payments))
			$counter++;	
		
		for ($i = 0; $i < $counter; $i++)
			{
				$payment_code[$row[$i]["CODE"]] = array("name" => $row[$i]["NAME"],"value" => "0");
			}
		
		return $payment_code;
	}
?>
