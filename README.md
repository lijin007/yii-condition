# yii-condition
将数组转换成yii数据库的查询条件
		$data = [
				'name1'=>1,
				'id'=>[1,2,3,4,5],
				'namestrs' => ['in',['str1','str2','str3']],
				'namelike'=>['like','namelike','AND',TRUE],
				'names' => [
					['like','names'],
					['<',4],
					['>=',1]
				],
				'_AND' =>  [
					[
					 'and1'=>1,
					 'and2'=>[2,2]
					],
					[
					 'and3'=>3,
					 'and4'=>[4,4]
					],
				],
				'_OR' => [
					[
					 'orname1'=>['like','orname1'],
					],
					[
					 'orname2'=>2,
					 'orname3'=>3
					],
				],
				'_EXPR' => [
					 'id=:id AND type=:type',
					 array(':id'=>1,':type'=>2)
				],
			];
