<?php
class Criteria extends CDbCriteria
{
	const PARAM_PREFIX=':iycp';

	/**
	 * [name=>3, name2=>[1,2,3],
	 *   namearr => [[like,3],[in,a]],
	 * ]
	 */
	public function cond( $map, $init=true ){
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
			$escape  = isset($val[2]) ? $val[2] : true; 

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
	 * 获取生成的sql, 仅仅用于查看
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
