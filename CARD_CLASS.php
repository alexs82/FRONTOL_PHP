<?php 
class CARD
{		
	////////////////////////////////////////////////////////////////////
	//			ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ С КЛАССОМ CARD				              //
	////////////////////////////////////////////////////////////////////
	public $NUMBER       	  ; #НОМЕР КАРТЫ 
	public $PREFIX_LENGTH	  ; #ДЛИНА ПРЕФИКСА. Используется для разделения видов карт
	
	////////////////////////////////////////////////////////////////////
	//				ФУНКЦИИ ДЛЯ РАБОТЫ С КЛАССОМ CARD				                //
	////////////////////////////////////////////////////////////////////
	//------------------------------------------------------------------
	# Функция получения списка документов, в которых использовалась карта. Учитываются только закрытые документы, в которых фигурирует карта.
	# $period_flag задает использовать период или выдать транзакции с картой за все время. Результаты сортируются по дате, затем по времени. 
	function GET_DOCS($DB_obj, $period_flag = '0',$date_begin = '',$date_end = '')
	{
	    $main_query = "SELECT DOCUMENT.ID, DOCUMENT.CLOSEDATE, DOCUMENT.CLOSETIME FROM DOCUMENT JOIN TRANZT ON DOCUMENT.ID = TRANZT.DOCUMENTID 
								WHERE TRANZT.INFOSTR LIKE '%$this->NUMBER%' AND TRANZT.TRANZTYPE = 55 AND DOCUMENT.STATE = 1 AND DOCUMENT.CHEQUETYPE = 0";
				
		$DB_obj->query = $period_flag ? $main_query.=" AND DOCUMENT.CLOSEDATE BETWEEN '$date_begin' AND '$date_end' ORDER BY 2,3" : $main_query.=" ORDER BY 2,3";
		$DB_obj->QUERY();
		
		return ($DB_obj->MAKE_ARRAY("ID"));
	}
	
	//------------------------------------------------------------------
	# Функция получения вида карты
	function GET_TYPE($DB_obj)
	{
		$DB_obj->query = "SELECT CARDPREFIXBEG,NAME FROM GRPCCARD";
		$DB_obj->QUERY();
	
		$card = array();
		$find = true;
		while ($DB_obj->NEXT() && $find)
			{
				$prefix = substr($this->NUMBER,0,$this->PREFIX_LENGTH); # ПОЛУЧЕНИЕ ПРЕФИКСА КАРТЫ
				if((substr($DB_obj->FIELD_VALUE("CARDPREFIXBEG"),0,$this->PREFIX_LENGTH)) == $prefix) # СРАВНЕНИЕ ПРЕФИКСА КАРТЫ С ПРЕФИКСАМИ ВИДОВ КАРТ
					{
						$card[$this->NUMBER] = $DB_obj->FIELD_VALUE("NAME");
						$find = false;
					}
			}
		if ($find)
			{
				$card[$this->NUMBER] = "ТИП НЕ УСТАНОВЛЕН";
			}
		$DB_obj->CLEAR_DATASET();
		
		return $card;
	}	
}
//
// КЛАСС СЧЕТЧИКОВ КАРТЫ
//
class CARD_COUNTER
{		
	////////////////////////////////////////////////////////////////////
	//	      ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ С КЛАССОМ CARD_COUNTER			      //
	////////////////////////////////////////////////////////////////////
	public  $COUNTERS_ID = array(); #МАССИВ СЧЕТЧИКОВ(ID) КАРТЫ 
	private $CARD;			        #НОМЕР КАРТЫ
	
	////////////////////////////////////////////////////////////////////
	//		   ФУНКЦИИ ДЛЯ РАБОТЫ С КЛАССОМ CARD_COUNTER			          //
	////////////////////////////////////////////////////////////////////
	//------------------------------------------------------------------
	# Конструктор класса, при создании необходим номер карты	
	function __construct($DB_obj,$CARD)
	{
 		$DB_obj->query = "SELECT CCARDCOUNTER.COUNTERID FROM CCARDCOUNTER JOIN CCARD 
						  ON CCARD.ID = CCARDCOUNTER.CCARDID WHERE CCARD.VAL = $CARD";
		$DB_obj->QUERY();
		
		$this->COUNTERS_ID = $DB_obj->MAKE_ARRAY("COUNTERID");
		$this->CARD = $CARD;
	}
	//------------------------------------------------------------------
	# Функция получения названий счетчиков карты в виде массива "№ счетчика" => "название"
	private function GET_COUNTER_NAME($DB_obj)
	{
		$OUTPUT = array();
		
		$DB_obj->query = "SELECT COUNTERTYPE.NAME, COUNTER.ID FROM COUNTERTYPE JOIN COUNTER
						  ON COUNTERTYPE.ID = COUNTER.COUNTERTYPEID WHERE COUNTER.ID IN (".implode(',', $this->COUNTERS_ID).")";
		$DB_obj->QUERY();
		
		while($DB_obj->NEXT())
			{
				$OUTPUT[$DB_obj->FIELD_VALUE("ID")] = $DB_obj->FIELD_VALUE("NAME");
			}	

		return $OUTPUT;			
	}
	//------------------------------------------------------------------
	# Функция подсчета значений счетчиков карты "№ счетчика" => "значение"
	private function GET_COUNTER_INFO($DB_obj)
	{
		$OUTPUT = array();
		
		for ($i = 0; $i < count($this->COUNTERS_ID); $i++)//ПОСЛЕДОВАТЕЛЬНО ПРОХОДИМ ВСЕ СЧЕТЧИКИ
			{
				$DB_obj->query = "SELECT SUM(DELTA) FROM COUNTERD WHERE COUNTERID = ".$this->COUNTERS_ID[$i];//СКЛАДЫВАЕМ ЗНАЧЕНИЯ СЧЕТЧИКА 
				$DB_obj->QUERY();
				
				$COUNTER_INFO = $DB_obj->MAKE_ARRAY('SUM');
				$OUTPUT[$this->COUNTERS_ID[$i]] = $COUNTER_INFO[0];
				$DB_obj->CLEAR_DATASET();
			}

		return $OUTPUT;
	}
	//------------------------------------------------------------------
	# Функция получения итоговых значений счетчиков "Название счетчика" => "Значение счетчика"
	function GET_COUNTER($DB_obj)
	{
		$counter_names  = $this->GET_COUNTER_NAME($DB_obj);
		$counter_values = $this->GET_COUNTER_INFO($DB_obj);
		
		ksort($counter_values);ksort($counter_names);
		$result = array();

		foreach ($counter_names as $key => $value)
			{
				$result[$value] = $counter_values[$key];
			}

		return $result;
	}
}
?>
