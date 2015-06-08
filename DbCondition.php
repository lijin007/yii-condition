<?php
/**
 * DbCondtion represents a query criteria, such as conditions, ordering by, limit/offset.
 *
 * It can be used in AR query methods such as CActiveRecord::find and CActiveRecord::findAll.
 *
 * $criteria=new DbCondtion();
 * $criteria->compare('status',Post::STATUS_ACTIVE);
 * $criteria->addInCondition('id',array(1,2,3,4,5,6));
 */
class DbCondition
{
	const PARAM_PREFIX=':_DC_';
	/**
	 * @var integer the global counter for anonymous binding parameters.
	 * This counter is used for generating the name for the anonymous parameters.
	 */
	public static $paramCount=0;
	/**
	 * @var string query condition. This refers to the WHERE clause in an SQL statement.
	 * For example, <code>age>31 AND team=1</code>.
	 */
	public $condition='';
	/**
	 * @var array list of query parameter values indexed by parameter placeholders.
	 * For example, <code>array(':name'=>'Dan', ':age'=>31)</code>.
	 */
	public $params=array();

	/**
	 * Constructor.
	 * @param array $data criteria initial property values (indexed by property name)
	 */
	public function __construct()
	{
	}
	/**
	 * Appends a condition to the existing {@link condition}.
	 * The new condition and the existing condition will be concatenated via the specified operator
	 * which defaults to 'AND'.
	 * The new condition can also be an array. In this case, all elements in the array
	 * will be concatenated together via the operator.
	 * This method handles the case when the existing condition is empty.
	 * After calling this method, the {@link condition} property will be modified.
	 * @param mixed $condition the new condition. It can be either a string or an array of strings.
	 * @param string $operator the operator to join different conditions. Defaults to 'AND'.
	 * @return  the criteria object itself
	 */
	public function addCondition($condition,$operator='AND')
	{
		if(is_array($condition))
		{
			if($condition===array())
				return $this;
			$condition='('.implode(') '.$operator.' (',$condition).')';
		}
		if($this->condition==='')
			$this->condition=$condition;
		else{
			//$this->condition='('.$this->condition.') '.$operator.' ('.$condition.')';
			$this->condition=$this->condition.' '.$operator.' '.$condition;
		}
			
		return $this;
	}

	/**
	 * Appends a search condition to the existing {@link condition}.
	 * The search condition and the existing condition will be concatenated via the specified operator
	 * which defaults to 'AND'.
	 * The search condition is generated using the SQL LIKE operator with the given column name and
	 * search keyword.
	 * @param string $column the column name (or a valid SQL expression)
	 * @param string $keyword the search keyword. This interpretation of the keyword is affected by the next parameter.
	 * @param boolean $escape whether the keyword should be escaped if it contains characters % or _.
	 * When this parameter is true (default), the special characters % (matches 0 or more characters)
	 * and _ (matches a single character) will be escaped, and the keyword will be surrounded with a %
	 * character on both ends. When this parameter is false, the keyword will be directly used for
	 * matching without any change.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @param string $like the LIKE operator. Defaults to 'LIKE'. You may also set this to be 'NOT LIKE'.
	 * @return DbCondtion the criteria object itself
	 */
	public function addSearchCondition($column,$keyword,$escape=true,$operator='AND',$like='LIKE')
	{
		if($keyword=='')
			return $this;
		if($escape)
			$keyword='%'.strtr($keyword,array('%'=>'\%', '_'=>'\_', '\\'=>'\\\\')).'%';
		$condition=$column." $like ".self::PARAM_PREFIX.self::$paramCount;
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$keyword;
		return $this->addCondition($condition, $operator);
	}

	/**
	 * Appends an IN condition to the existing {@link condition}.
	 * The IN condition and the existing condition will be concatenated via the specified operator
	 * which defaults to 'AND'.
	 * The IN condition is generated by using the SQL IN operator which requires the specified
	 * column value to be among the given list of values.
	 * @param string $column the column name (or a valid SQL expression)
	 * @param array $values list of values that the column value should be in
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return DbCondtion the criteria object itself
	 */
	public function addInCondition($column,$values,$operator='AND')
	{
		if(($n=count($values))<1)
			$condition='0=1'; // 0=1 is used because in MSSQL value alone can't be used in WHERE
		elseif($n===1)
		{
			$value=reset($values);
			if($value===null)
				$condition=$column.' IS NULL';
			else
			{
				$condition=$column.'='.self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
		}
		else
		{
			$params=array();
			foreach($values as $value)
			{
				$params[]=self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
			$condition=$column.' IN ('.implode(', ',$params).')';
		}
		return $this->addCondition($condition,$operator);
	}

	/**
	 * Appends an NOT IN condition to the existing {@link condition}.
	 * The NOT IN condition and the existing condition will be concatenated via the specified operator
	 * which defaults to 'AND'.
	 * The NOT IN condition is generated by using the SQL NOT IN operator which requires the specified
	 * column value to be among the given list of values.
	 * @param string $column the column name (or a valid SQL expression)
	 * @param array $values list of values that the column value should not be in
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return DbCondtion the criteria object itself
	 * @since 1.1.1
	 */
	public function addNotInCondition($column,$values,$operator='AND')
	{
		if(($n=count($values))<1)
			return $this;
		if($n===1)
		{
			$value=reset($values);
			if($value===null)
				$condition=$column.' IS NOT NULL';
			else
			{
				$condition=$column.'!='.self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
		}
		else
		{
			$params=array();
			foreach($values as $value)
			{
				$params[]=self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
			$condition=$column.' NOT IN ('.implode(', ',$params).')';
		}
		return $this->addCondition($condition,$operator);
	}

	/**
	 * Appends a condition for matching the given list of column values.
	 * The generated condition will be concatenated to the existing {@link condition}
	 * via the specified operator which defaults to 'AND'.
	 * The condition is generated by matching each column and the corresponding value.
	 * @param array $columns list of column names and values to be matched (name=>value)
	 * @param string $columnOperator the operator to concatenate multiple column matching condition. Defaults to 'AND'.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return DbCondtion the criteria object itself
	 */
	public function addColumnCondition($columns,$columnOperator='AND',$operator='AND')
	{
		$params=array();
		foreach($columns as $name=>$value)
		{
			if($value===null)
				$params[]=$name.' IS NULL';
			else
			{
				$params[]=$name.'='.self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
		}
		return $this->addCondition(implode(" $columnOperator ",$params), $operator);
	}
	/**
	 * Adds a between condition to the {@link condition} property.
	 *
	 * The new between condition and the existing condition will be concatenated via
	 * the specified operator which defaults to 'AND'.
	 * If one or both values are empty then the condition is not added to the existing condition.
	 * This method handles the case when the existing condition is empty.
	 * After calling this method, the {@link condition} property will be modified.
	 * @param string $column the name of the column to search between.
	 * @param string $valueStart the beginning value to start the between search.
	 * @param string $valueEnd the ending value to end the between search.
	 * @param string $operator the operator used to concatenate the new condition with the existing one.
	 * Defaults to 'AND'.
	 * @return DbCondtion the criteria object itself
	 * @since 1.1.2
	 */
	public function addBetweenCondition($column,$valueStart,$valueEnd,$operator='AND')
	{
		if($valueStart==='' || $valueEnd==='')
			return $this;

		$paramStart=self::PARAM_PREFIX.self::$paramCount++;
		$paramEnd=self::PARAM_PREFIX.self::$paramCount++;
		$this->params[$paramStart]=$valueStart;
		$this->params[$paramEnd]=$valueEnd;
		$condition="$column BETWEEN $paramStart AND $paramEnd";

		return $this->addCondition($condition,$operator);
	}
	/**
	 * [name=>3, name2=>[1,2,3],
	 *   namearr => [[like,3],[in,a]],
	 * ]
	 */
	public function getCondition( $map, $init=true ){
		if($init){
			$this->condition = '';
			$this->params = array();
		}
		$this->condition = $this->_getCondition( $map );
	}
	/**
	 * 同上
	 */
	private function _getCondition( $map ){
		// 保存数据
		$condition = [];
		foreach( $map as $name=>$val ){
			if( $name == '_AND' ){
				// and condition
				$condition[] = $this->logicCond($val, 'AND');
				
			}elseif( $name == '_OR' ){
				$condition[] = $this->logicCond($val, 'OR');

			}elseif($name == '_EXPR'){
				// 手写sql: _EXPR=>[type=:type,name=:name,[:name=smith,:type=2]]
				$val2 = array('EXPR',$val);
				$condition[] = $this->_condition($name, $val2);
				
			}elseif( is_array($val) && is_array($val[0]) ){
				// etc. 'subtype' => [['<',4],['>=',1]] -> subtype>=1 AND subtype<4
				$temps=[];
				foreach($val as $sub){
					$condition[] = $this->_condition($name, $sub);
				}
				
			}else{
				// etc. name=3; name=john; type=[like,3] -> type like '%3%'
				$condition[] = $this->_condition($name, $val);
			}
		
		}
		return implode(' AND ',$condition);
	}
	/**
	 * 分组
	 * to: expr1 and expr2 and expr3
	 */
	private function logicCond( $val, $operate ){
		$condition = [];
		foreach($val as $k=>$v ){
			$condition[] = '(' . $this->_getCondition( $v ) . ')';
		}
		return ' ( '. implode(" {$operate} ", $condition) .' ) ';
	}
	/**
	 * 键值对转换
	 * @param str $column 字段名称
	 * @param $val 字段值:  number | str | 一维数组
	 */
	private function _condition($column, $val, $operator = 'AND'){
		//1  解析操作符  op value [escape]
		if( is_array($val) ){
			// etc: $val = [like,3,true] ; $val=[12,3,4] | [IN,[12,3,4]]
			if( isset($val[0]) ){
				if( is_numeric($val[0]) ){
					$op = 'IN';
					$value = $val;
				}else{
					$op = trim($val[0]);
					$op = strtoupper($op);
					$value = $val[1];
				}
			}else{
				$op = 'IN';
				$value = array(null);
			}

			//2  只有 LIKE, NOTLIKE 含有. 是否自动两边加'%', %{keywork}%查询
			$escape  = isset($v[2]) ? $v[2] : true; 

		}elseif($val===NULL){
			// 转化成 is null
			$op = 'IN';
			$value = array(NULL);
			
		}else{
			// $val=johnny | $val=3
			$op = '=';
			$value = $val;
			
		}

		//2  转换操作
		$this->condition = '';
		switch($op){
			case '=':
				$this->addCondition($column.$op.self::PARAM_PREFIX.self::$paramCount,$operator);
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
				break;
				
			case 'IN':
				$this->addInCondition($column,$value,$operator);
				break;
				
			case 'NOTIN':
				$this->addNotInCondition($column,$value,$operator);
				break;
				
			case 'LIKE':
				$this->addSearchCondition($column,$value,$escape,$operator);
				break;
				
			case 'NOTLIKE':
				$this->addSearchCondition($column,$value,$escape,$operator,'NOT LIKE');
				break;
				
			case 'EXPR':
				$this->addCondition($value[0], $operator);
				$this->params = array_merge($this->params,$value[1]);
				break;
				
			case 'BETWEEN':
				$this->addBetweenCondition($column,$v[1][0],$v[1][1],$operator);
				break;
				
			default:
				// '<', '<=', '>', '>=', '<>', '!='
				$this->addCondition($column.$op.self::PARAM_PREFIX.self::$paramCount,$operator);
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
		}
		return $this->condition;
	}
	/**
	 * 过滤like字段值
	 */
	public function filerLike($keyword){
		return strtr($keyword,array('%'=>'\%', '_'=>'\_', '\\'=>'\\\\'));
	}
	/**
	 * 获取生成的sql
	 */
	public function getCondiSql(){
		$sql = $this->condition;
		$params = $this->params;
	  	$params =  array_reverse($params,true);
	  	foreach($params as $search => $replace){
	  		$sql = str_replace($search, "'{$replace}'", $sql);
	  	}
    	return $sql;
	}
}

