<?php 
//require_once ('COMMON_LIB.php');

class SHOP
{	
	////////////////////////////////////////////////////////////////////
	//			ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ С КЛАССОМ SHOP				  //
	////////////////////////////////////////////////////////////////////
	private $NUMBER; #НОМЕР МАГАЗИНА(RMK.CODE)
	
	////////////////////////////////////////////////////////////////////
	//				ФУНКЦИИ ДЛЯ РАБОТЫ С КЛАССОМ SHOP				  //
	////////////////////////////////////////////////////////////////////
	//------------------------------------------------------------------
	# Функция установки номера магазина
	function SET($shop_number)
	{
		$this->NUMBER = $shop_number;
	}
	//------------------------------------------------------------------
	# Функция получения номера магазина
	function GET()
	{
		if($this->NUMBER)
			return $this->NUMBER;
		else
		    return -1;
	}
	//------------------------------------------------------------------
	# Функция получения данных о магазине (ID в БД, название, имя в БД)
	function GET_NAME ($DB_obj)
	{
		$DB_obj->query = "SELECT ID, CODE, NAME, TEXT FROM RMK WHERE CODE IN ($this->NUMBER)";
		$DB_obj->QUERY();
		
		return $DB_obj->MAKE_ARRAY();
	}
	//------------------------------------------------------------------
	# Функция подсчета показателей магазина за период (количество чеков, сумма чеков, средний чек и т.д.)
	function GET_STAT ($DB_obj, $date_begin, $date_end)
	{
		$DB_obj->query = "SELECT COUNT(*), SUM(SUMMWD), AVG(SUMMWD), MIN(SUMMWD), MAX(SUMMWD), 
						  SUM(SUMM-SUMMWD) AS DISCOUNT FROM DOCUMENT WHERE (DOCUMENT.STATE = 1) 
						  AND CLOSEDATE BETWEEN '$date_begin' AND '$date_end' AND (NSHOP IN ($this->NUMBER))";
		$DB_obj->QUERY();	
		
		return $DB_obj->MAKE_ARRAY();
	}
	//------------------------------------------------------------------
	# Функция подсчета количества закрытых чеков по суммам (массив $range задает диапазоны: 
	# к примеру $range = {0;100;1000} посчитает кол-во чеков в 2-ух диапазонах (>0 и <100);(>100 и <1000)); $operation: 1 - продажа, 2 - возврат.
	function AVG_CHECK ($DB_obj, $date_begin, $date_end, $range, $operation = '1')
	{	
		$result = array();
		
		for ($i = 0; $i < (count($range)-1); $i++)
			{
				$result[$i] = array();
				$DB_obj->query = "SELECT COUNT(*), SUM(SUMMWD), SUM(SUMM-SUMMWD) AS DISCOUNT FROM DOCUMENT WHERE (DOCUMENT.STATE = 1) AND (DOCUMENT.DOCKINDID = '$operation')
								  AND DOCUMENT.CLOSEDATE BETWEEN '$date_begin' AND '$date_end' AND ABS(SUMMWD) BETWEEN ".($range[$i] + 1)." AND ".$range[$i+1]." 
								  AND (DOCUMENT.NSHOP IN ($this->NUMBER))";
				$DB_obj->QUERY();	
				$DB_obj->NEXT();
				
				array_push($result[$i],$DB_obj->FIELD_VALUE('COUNT'),abs($DB_obj->FIELD_VALUE('SUM')),abs($DB_obj->FIELD_VALUE('DISCOUNT')));//($range[$i]."-".$range[$i+1])
				$DB_obj->CLEAR_DATASET();
			}
			
		return $result;
	}
	//-----------------------------------------------------------------
	# Функция формирования списка транзакций. Переменными $rows и $offset указывается кол-во строк и смещение для выборки.  
	function GET_TRANZ ($DB_obj, $date_begin, $date_end, $offset = '0', $rows = '0')
	{
		$prefix = $rows ? "SELECT FIRST ".$rows." SKIP ".$offset : "SELECT";
		
		$DB_obj->query = $prefix." DOCUMENT.ID, DOCUMENT.SUMMWD, DOCUMENT.OPENDATE, DOCUMENT.OPENTIME, DOCUMENT.CLOSEDATE,
			 DOCUMENT.CLOSETIME, DOCUMENT.SUMM, DOCUMENT.CHEQUETYPE, DOCUMENT.NSHOP, (DOCUMENT.SUMM-DOCUMENT.SUMMWD) AS DISCOUNT FROM DOCUMENT WHERE
			(DOCUMENT.STATE = 1) AND DOCUMENT.CLOSEDATE BETWEEN '$date_begin' AND '$date_end' AND (DOCUMENT.NSHOP IN ($this->NUMBER))";
		$DB_obj->QUERY();	
		
		return $DB_obj->MAKE_ARRAY();
	}
	//-----------------------------------------------------------------
	# Функция формирования списка номеров транзакций. Переменными $rows и $offset указывается кол-во строк и смещение для выборки. 
	# Возвращает ID "закрытых"(статус документа во FRONTOL - "закрыт") транзакций, для последующего анализа, к примеру получения списка использованных карт. 
	function GET_TRANZ_NUMBERS ($DB_obj, $date_begin, $date_end, $offset = '0', $rows = '0')
	{
		$prefix = $rows ? "SELECT FIRST ".$rows." SKIP ".$offset : "SELECT";
	
		$DB_obj->query = $prefix." DOCUMENT.ID FROM DOCUMENT WHERE
			(DOCUMENT.STATE = 1) AND DOCUMENT.CLOSEDATE BETWEEN '$date_begin' AND '$date_end' AND (DOCUMENT.NSHOP IN ($this->NUMBER))";
		$DB_obj->QUERY();	
		
		return $DB_obj->MAKE_ARRAY("ID");
	}
}
?>
