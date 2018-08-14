<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb;

use PDO;

/**
 * 数据库操作类
 *
 * 继承此类到Dao可以选择重构父类构造方法，以指定表名
 * 不重构则自动获取类名Dao前面到字段为表名
 *
 * @package nb
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/3/30
 *
 * @method static \nb\dao\Driver test($distinct = false)
 *
 * @method \nb\dao\Driver field($fieldName)
 * @method \nb\dao\Driver left($table, $on = '', $server=null, $fields = '')
 * @method \nb\dao\Driver where($condition, $params = NULL)
 * @method \nb\dao\Driver orderby($order)
 *
 * @property  PDO db
 */
class Dao extends Component {

	/**
	 * Driver对象
	 * @var \nb\dao\Driver
	 */
	public $driver;

    public static function config($name = 'dao') {
        // TODO: Implement config() method.
        if(is_string($name)) {
            $ser = Config::getx($name);
            $ser or $ser = conf($name);
            $name = $ser;
        }
        return $name;
    }

    /**
     * 获取驱动对象
     * @return \nb\dao\Driver
     */
    public static function driver($table=null,$pk='id',$server = 'dao'){
        $class = get_called_class();
        $key = $class.':'.$table.':'.(is_string($server)?$server:md5(json_encode($server)));
        $driver = Pool::get($key);

        if($driver) {
            return $driver;
        }
        if($table===null) {
            $class = explode('\\',$class);
            $table = end($class);
        }
        $config  = static::config();

        $driver  = self::parse(get_class(),$config);

        $driver  = new $driver($table,$pk,$config);

        Pool::set($key,$driver);

        return $driver;
    }


	public static function query() {

    }

    public static function execute() {

    }

    /**
     * 获取对象
     * @return $this
     */
    public static function table($tableName=null,$id='id',$server = 'dao'){
        $obj = get_called_class();
        $alias = $obj;
        if('nb\Dao' == $obj) {
            $alias .= '@'.$tableName;
        }
        return Pool::object($alias,$obj,[
            $tableName,
            $id,
            $server
        ]);
    }

	/**
	 * 设置表别名
	 * @param $tableAlias
	 * @return Dao
	 */
	public function alias($tableAlias) {
		$this->driver->alias($tableAlias);
		return $this;
	}

    /**
     * @param bool $class
     * @return Dao
     */
	public function object($class = true) {
        $this->driver->object($class);
        return $this;
    }

	
    /**
     * 向数据库添加一条数据
     *
     * @param array $arr
     * @param boolean $filter 是否过滤掉值不为真的数据，true为去掉，false不去掉，默认不去掉
     */
    public function insert($arr,$filter=false) {
        if($filter) $arr = array_filter($arr,$filter);
        return $this->driver->insert($arr);
    }
	
	/**
	 * 向数据库批量添加数据
	 * @param array $arr
	 * @param unknown $fieldNames
	 */
	public function inserts($arr, $fieldNames=[]) {
		return $this->driver->batchInsert($arr,$fieldNames);
	}
	
	/**
	 * 添加数据时，如果UNIQUE索引或PRIMARY KEY中出现重复值，则执行旧行UPDATE。
	 * @param array $arr 要添加的数据
	 * @param string $upstr 要执行的修改语句
	 */
	public function insertOrUpdate($arr,$upstr = null){
		return $this->driver->insertOrUpdate($arr,$upstr);
	}
	
	/**
	 * 根据ID修改数据
	 * @param string&int $id
	 * @param array $arr
	 */
	public function updateId($id, $data, $params=[], $filter=false) {
		return $this->driver->where("{$this->driver->id}=?", [$id])->update($data,$params);
	}

	/**
	 * 根据条件修改数据
	 * @param string&int $id
	 * @param array $arr
	 */
	public function update($arr, $condition=null, $params=[], $filter=false) {
		if($condition) {
			$this->driver->where($condition, $params);
		}
		return $this->driver->update($arr);
	}

	/**
	 * 根据条件删除数据
	 * @param string&int $id
	 */
	public function delete($condition=null, $params=null) {
		return $this->driver->delete($condition, $params);
	}

	/**
	 * 根据ID删除数据
	 * @param string&int $id
	 */
	public function deleteId($id) {
		return $this->driver->delete("{$this->driver->id}=?", $id);
	}

	/**
	 * 根据主键查找数据
     *
	 * @param $id
	 * @param string $fields
	 * @param int $fetchMode
	 * @return array
	 */
	public function findId($id, $fields = '', $fetchMode=PDO::FETCH_ASSOC) {
		if (!empty($fields)) $this->driver->field($fields);
		return $this->driver->where($this->driver->id.'=?', $id)
            ->fetch($fetchMode);
	}

	/**
	 * 从结果集中的下一行返回单独的一列，如果没有了，则返回 FALSE
	 * @param string&int $id
	 * @param string $column 列值
	 */
	public function findIdColumn($id, $column) {
		if (!empty($column)) $this->driver->field($column);
		$tableAlias = $this->driver->alias?$this->driver->alias.'.':'';
		return $this->driver->where($tableAlias.$this->id.'=?', [$id])
            ->fetchColumn();
	}

	/**
	 * 获取结果数量
	 * @param string $condition
	 * @param string&array $params
	 * @param string $fields
	 */
	public function findColumn($condition, $params, $fields) {
		if (!empty($fields)) $this->driver->field($fields);
		return $this->driver->where($condition, $params)
            ->fetchColumn();
	}

	/**
	 * 获取唯一结果
	 * @param string&array $condition
	 * @param string $fields
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 */
	public function findsUnique($condition = '', $fields = '', $rows = 0, $start = 0, $order='') {
		if (is_array($condition)) {
			$where = $condition[0];
			$params = $condition[1];
		} else {
			$where = $condition;
			$params = null;
		}
		return $this->driver->field($fields)
            ->where($where, $params)
            ->orderby($order)
            ->limit($rows, $start)
            ->fetchAllUnique();
	}

	
	/**
	 * 获取一条结果
	 * @param $condition
	 * @param null $params
	 * @param null $fields
	 * @param int $fetchMode
     *
     * @return bool|array
	 */
	public function find($condition, $params = NULL, $fields=null, $fetchMode=PDO::FETCH_ASSOC) {
		if (!empty($fields)) $this->driver->field($fields);
		return $this->driver
            ->where($condition, $params)
            ->fetch($fetchMode);
	}

	/**
	 * 获取多条结果
	 * @param string&array $condition
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 * @param string $fields
	 * @param number $fetchMode
	 */
	public function finds($condition = NULL, $rows = 0, $start = 0, $order='', $fields = '*', $fetchMode=PDO::FETCH_ASSOC) {
		if (is_array($condition)) {
			$where = $condition[0];
			$params = $condition[1];
		} 
		else {
			$where = $condition;
			$params = null;
		}
		return $this->driver
            ->field($fields)
            ->where($where, $params)
            ->orderby($order)
            ->limit($rows, $start)
            ->fetchAll($fetchMode);
	}

	/**
	 * 获取多条结果,无翻页参数
	 * @param string&array $condition
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 * @param string $fields
	 * @param number $fetchMode
	 */
	public function fetchs($condition = NULL, $params = NULL, $fields=null, $order='',$fetchMode=PDO::FETCH_ASSOC) {
		return $this->driver
            ->field($fields)
            ->where($condition, $params)
            ->orderby($order)
            ->fetchAll($fetchMode);
	}


	/**
	 * 获取结果集和数总量
	 * @param string&array $condition
	 * @param number $rows
	 * @param number $start
	 * @param string $order
	 * @param string $fields
	 * @param number $fetchMode
	 * @return array(n,s)
	 */
	public function findsPage($condition = '', $rows = 0, $start = 0, $order='', $fields = '*', $fetchMode=PDO::FETCH_ASSOC) {
        $driver = $this->driver
            ->where($condition[0],$condition[1])
            ->limit($rows,$start)
            ->orderby($order)
            ->field($fields);
        return $driver->fetchPage($fetchMode);
	}

    /**
     * 获取结果集和总数量
     * @param string&array $condition
     * @param number $rows
     * @param number $start
     * @param string $order
     * @param string $fields
     * @param number $fetchMode
     * @return [n,s]
     */
    public function paginate($rows = 0, $start = 0, $condition = '', $order='', $fields = '*', $fetchMode=PDO::FETCH_ASSOC) {
        $driver = $this->driver;

        is_array($condition) and $driver->where($condition[0],$condition[1]);
        $order and $driver->orderby($order);
        $driver->limit($rows,$start)->field($fields);
        return $driver->fetchPage($fetchMode);
    }

	/**
	 * 获取指定首字段为key的结果集
	 * 如果只有两个字段,则v中没有key值,多于两个字段,则v中包含所有字段
	 * @param $field
	 * @param null $condition
	 * @param null $params
	 * @return array
	 */
	public function findKv($field,$condition = NULL, $params = NULL){
		return $this->driver
            ->field($field)
            ->where($condition,$params)
            ->fetchKv();
	}

	
	/**
	 * 获取结果集数量
	 * @param string $condition
	 * @param string $params
	 * @param string $distinct
	 */
	public function count($condition = null, $params = null, $distinct=false) {
		if ($condition) {
			$this->driver->where($condition, $params);
		}
		return $this->driver->count($distinct);
	}

	/**
	 * 检查数据是否存在
	 * @param string $condition
	 * @param string $params
	 * @return boolean
	 */
	public function exists($condition='', $params=null) {
		$cnt = $this->driver
            ->field('count(*)')
            ->where($condition, $params)
            ->fetchColumn();
		return $cnt > 0 ? true : false;
	}


    /**
     * 获取自增主键的最大值
     * @return mixed
     */
	public function maxId() {
        return $this->driver->field('max('.$this->driver->id.')')->fetchColumn();
	}

    /**
     * 获取自增主键的最小值
     * @return mixed
     */
	public function minId(){
		return $this->driver->field('min('.$this->driver->id.')')->fetchColumn();
	}

	/**
	 * 表链接
	 * @param string $table
	 * @param string $on
	 * @param string $fields
	 * @param string $joinType
	 * @return $this
	 */
	public function has($table,  $on, $server=null, $fields='', $joinType='left'){
		$joinType = $joinType.' JOIN';	
		$this->driver->join($table, $on, $server, $fields, $joinType);
		return $this;
	}
	
	/**
	 * 开始事务
	 */
	public function beginTransaction() {
		$this->driver->db->beginTransaction();
	}

    /**
     * 提交事务
     */
	public function commit() {
		$this->driver->db->commit();
	}
	
	/**
	 * 回滚事务
	 */
	public function rollback() {
		$this->driver->db->rollback();
	}

	/**
	 * 获取运行的SQL语句
	 */
	public function lastSql() {
		return $this->driver->sql();
	}

	/**
	 * 获取当前DAO对应的数据表表名
	 * @return string
	 */
	public function tblName($needPrefix=false) {
		return $this->driver->getTable($needPrefix);
	}
	
	/**
	 * 删除表
	 */
	public function truncate() {
		$this->driver->truncate();
	}


    /**
     * 对象方法的静态调用
     * @param $name
     * @param $arguments
     * @return mixed|null
     */
    //public static function __callStatic($name, $arguments) {
    //    $method =substr($name,1);
    //    $that = self::driver();
    //    if (method_exists($that, $method)) {
    //        return call_user_func_array([$that,$method],$arguments);
    //    }
    //    return null;
    //}

    /**
     * 对类库里的方法静态调用
     * @param $name
     * @param $arguments
     * @return self
     */
    public static function __callStatic($method, $arguments) {

        // TODO: Implement __callStatic() method.
        $that = static::driver();
        if (method_exists($that, $method)) {
            return call_user_func_array([$that,$method],$arguments);
        }
        return null;
    }
}
