# yii-condition
yii2.0已经支持数组查询了,这个适合于1.0.   注意跟2.0的不太一样啊
将数组转换成yii数据库的查询条件

改写自 CDbCriteria, 将简化一些常用的查询. 将其放在protected/components/目录下即可

####1 直接代替 CDbCriteria 使用

~~~php

        $model = new Ziku();
	// 自带的查询条件是这样子的
        $criteria = new CDbCriteria();
        $criteria->condition="zi LIKE :zi AND id IN (:ycp1, :ycp2, :ycp3) AND type>=:iycp4 AND type<:iycp5";
		$criteria->params = [
		    ':zi' => '%乒%',
		    ':ycp1' => 49,
		    ':ycp2' => 1,
		    ':ycp3' => 2,
		    ':iycp4' => 1,
		    ':iycp5' => 4,
		];
        $rows = $model->findAll($criteria);
		//$data = $data -> getData();
		print_r( $rows );
		
	// 使用将简化
	$cond = [
		'zi' => ['like','乒'],
		'id' => [49,1,2],
		'type' => [ ['>=',1],  ['<',4] ]
	];
		
        $criteria = new Criteria();
        $criteria->cond( $cond ); 
        $rows = $model->findAll($criteria);

~~~

####2 生成条件供复杂查询使用

~~~php

$cond = [
	'z.id' => [['>=',1],  ['<',400]],
	'z.type' => 1
];
$criteria = new Criteria();
$criteria->cond( $cond ); 
	
$rows =  Yii::app()->db->createCommand()
          ->select('count(1) AS total,z.id')
          ->from( "ziku z" )
          ->leftJoin("zi_tag zt","z.id=zt.zi_id")
          ->where($criteria->condition, $criteria->params)
	  ->group('z.id')
	  ->offset(0)
	  ->limit(10)
	  ->queryAll();

~~~

####3 常用功能

~~~php
[
'name1'=>1, // name1='1' 
'id'=>[1,2,3,4,5], // id IN ('1', '2', '3', '4', '5') 
'namestrs' => ['in',['str1','str2','str3']], // namestrs IN ('str1', 'str2', 'str3') 
'namelike1'=>['like','namelike',TRUE],//namelike1 LIKE '%namelike%' 
'namelike2'=>['like','namelike%',FALSE],// namelike2 LIKE 'namelike%' 
'type' => [ //type<'4' AND type>='1'
	['<',4],
	['>=',1]
],
'_AND' =>  [// ( (and1='1' AND and2 IN ('2', '2')) AND (and3='3' AND and4 IN ('4', '4'))
	[
	 'and1'=>1,
	 'and2'=>[2,2]
	],
	[
	 'and3'=>3,
	 'and4'=>[4,4]
	],
],
'_OR' => [//( (orname1 LIKE '%orname1%') OR (orname2='2' AND orname3='3') ) 
	[
	 'orname1'=>['like','orname1'],
	],
	[
	 'orname2'=>2,
	 'orname3'=>3
	],
],
'_EXPR' => [ // 直接使用原生的 id='1' AND type='2'
	 'id=:id AND type=:type',
	 array(':id'=>1,':type'=>2)
		],
];

~~~
