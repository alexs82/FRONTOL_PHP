<?php 
require_once ('COMMON_LIB.php');

class DOCUMENT
{		
	////////////////////////////////////////////////////////////////////
	//	          ПЕРЕМЕННЫЕ ДЛЯ РАБОТЫ С КЛАССОМ DOCUMENT            //
	////////////////////////////////////////////////////////////////////
	public $ID; #ID ДОКУМЕНТА(DOCUMENT.ID, TRANZT.DOCUMENTID)
	
	////////////////////////////////////////////////////////////////////
	//	          ФУНКЦИИ ДЛЯ РАБОТЫ С КЛАССОМ DOCUMENT	              //
	////////////////////////////////////////////////////////////////////
	//------------------------------------------------------------------
	# Функция получения сумм по видам оплат в документе
	function GET_PAYS($DB_obj)
	{
		$DB_obj->query = "SELECT TRANZT.SUMM, TRANZT.INFO FROM TRANZT WHERE (TRANZT.DOCUMENTID = '$this->ID') AND (TRANZT.TRANZTYPE IN (40,41))";
		$DB_obj->QUERY();
		
		$payments = GET_PAYS_CODE($DB_obj->link); # Функция из COMMON_LIB.php
		while ($DB_obj->NEXT())
			{
				$payments[$DB_obj->FIELD_VALUE("INFO")]["value"] = $payments[$DB_obj->FIELD_VALUE("INFO")]["value"] + $DB_obj->FIELD_VALUE("SUMM");
			}
			
		return $payments;
	}
	//------------------------------------------------------------------------------------------
	# Функция получения номеров карт и их названий
	function GET_CARDS($DB_obj)
	{		
		// Получаем строку содержащую все карты документа
		$DB_obj->query = "SELECT TRANZT.INFOSTR FROM TRANZT WHERE TRANZT.DOCUMENTID = '$this->ID'";
		$DB_obj->QUERY(); 
		$DB_obj->NEXT();
		$cards_str = $DB_obj->FIELD_VALUE("INFOSTR");
		
		// Блок перменных
		$CARD_LENGTH = 13; 			# Константа - длина карты (для использования карт со штрихкодом EAN-13)
		$card_list = array();				# Итоговый массив карт
		$card_counter = 0;					# Счетчик карт
		$total_length = strlen($cards_str); # Длина массива карт
		
		while(($total_length - $CARD_LENGTH*$card_counter) >= $CARD_LENGTH) 
			{
				$card_list[$card_counter] = substr($cards_str,($card_counter + $card_counter*$CARD_LENGTH),13);
				$card_counter++;
			}
		
		return $card_list;
	}
	//------------------------------------------------------------------------------------------
	# Функция получения списка позиций документа
	/*
		На выходе получаем массив, в котором каждый элемент - ассоциативный массив вида:
		"имя поля" => "значение" (пр. "MARK" => "У5671"), "имя поля" => "значение" (пр. "PRICE" => "52"), и т.д.
	*/
	function GET_ITEMS($DB_obj)
	{
		$DB_obj->query = "SELECT TRANZT.DOCUMENTID, TRANZT.TRANZDATE, TRANZT.TRANZTYPE, TRANZT.TRANZTIME,
						  TRANZT.PRICE, TRANZT.QUANTITY, TRANZT.PRICEWD, TRANZT.SUMMWD, TRANZT.TRMKID, SPRT.MARK, SPRT.NAME FROM TRANZT JOIN SPRT ON 
						  TRANZT.WARECODE=SPRT.CODE WHERE TRANZT.DOCUMENTID = '$this->ID' AND TRANZT.TRANZTYPE IN (11,12)";
		$DB_obj->QUERY(); 
		
		return $DB_obj->MAKE_ARRAY();
	}
	//------------------------------------------------------------------------------------------
	# Функция получения произвольного поля документа
	/*
		На выходе получаем значение поля документа.
	*/
	function GET_FIELD($DB_obj, $FIELD_NAME)
	{
		$DB_obj->query = "SELECT ".$FIELD_NAME." FROM DOCUMENT WHERE ID = '$this->ID'";
		$DB_obj->QUERY(); 
		$DB_obj->NEXT();
		
		return $DB_obj->FIELD_VALUE($FIELD_NAME);
	}
	//------------------------------------------------------------------------------------------
	# Функция получения списка позиций документа по артикулу товара (для получения списка по коду заменить TRANZT.WAREMARK на TRANZT.CODE)
	function GET_ITEMS_BY_CODE($DB_obj,$CODE)
	{
		$DB_obj->query = "SELECT TRANZT.DOCUMENTID, TRANZT.TRANZDATE, TRANZT.TRANZTIME,
						  TRANZT.PRICE, TRANZT.QUANTITY, TRANZT.PRICEWD, TRANZT.SUMMWD FROM TRANZT
						  WHERE TRANZT.DOCUMENTID = '$this->ID' AND TRANZT.TRANZTYPE IN (11,12) AND TRANZT.WAREMARK = '$CODE'";
		$DB_obj->QUERY(); 
		
		return $DB_obj->MAKE_ARRAY();
	}
	//-----------------------------------------------------------------
	# Функция формирования сведений о продаже
	function GET_INFO ($DB_obj)
	{
		$DB_obj->query = "SELECT DOCUMENT.ID, DOCUMENT.SUMMWD, DOCUMENT.OPENDATE, DOCUMENT.OPENTIME, DOCUMENT.CLOSEDATE,
			 DOCUMENT.CLOSETIME, DOCUMENT.SUMM, DOCUMENT.CHEQUETYPE, DOCUMENT.NSHOP, (DOCUMENT.SUMM-DOCUMENT.SUMMWD) AS DISCOUNT FROM DOCUMENT WHERE
			(DOCUMENT.STATE = 1) AND DOCUMENT.ID = '$this->ID'";
		$DB_obj->QUERY();	
		
		return $DB_obj->MAKE_ARRAY();
	}
}
?>
