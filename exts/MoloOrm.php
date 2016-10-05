<?php
/**
 * -----------------------------------------------------------------------------
 * MoloOrm
 * -----------------------------------------------------------------------------
 * @author      mjix (http://twitter.com/mjix)
 * @package     MoloOrm (https://github.com/mjix/MoloOrm/)
 *
 * @copyright   (c) 2012 mjix (http://github.com/mjix)
 * @license     MIT
 * -----------------------------------------------------------------------------
 *
 * About MoloOrm
 *
 * MoloOrm modify from VoodooPHP; MoloOrm is a simple-ORM which functions as both a fluent select query API and a CRUD model class.
 * MoloOrm is built on top of PDO and is well fit for small to mid-sized projects, where the emphasis 
 * is on simplicity and rapid development rather than infinite flexibility and features.
 * MoloOrm works easily with table relationship.
 * 
 * Learn more: https://github.com/mjix/MoloOrm
 * 
 */

namespace exts;

use ArrayIterator,
    PDO,
    DateTime;

class MoloOrm
{
    const NAME              = "MoloOrm";
    const VERSION           = "1.0.0";

    // RELATIONSHIP CONSTANT
    const REL_HASONE        =  1;       // OneToOne. Eager Load data
    const REL_LAZYONE       = -1;     // OneToOne. Lazy load data
    const REL_HASMANY       =  2;      // OneToMany. Eager load data
    const REL_LAZYMANY      = -2;    // OneToOne. Lazy load data
    const REL_HASMANYMANY   =  3;  // ManyToMany. Not implemented

    const OPERATOR_AND = " AND ";
    const OPERATOR_OR  = " OR ";
    const ORDERBY_ASC = "ASC";
    const ORDERBY_DESC = "DESC";
    
    protected $pdo = null;
    private $pdo_stmt = null;
    protected $table_name = "";
    private $table_token = "";
    protected $table_alias = "";
    protected $is_single = false;
    private $select_fields = [];
    private $join_sources = [];
    private $limit = null;
    private $offset = null;
    private $order_by = [];
    private $group_by = [];
    private $where_parameters = [];
    private $where_conditions = [];
    private $and_or_operator = self::OPERATOR_AND;
    private $having = [];
    private $wrap_open = false;
    private $last_wrap_position = 0;
    private $is_fluent_query = true;
    private $pdo_executed = false;
    private $_data = [];
    private $_objdata = [];
    private $debug_sql_query = false;
    private $sql_query = "";
    private $sql_parameters = [];
    private $_dirty_fields = [];
    private $query_profiler = [];
    private $reference_keys = [];
    private static $references = [];

    // Table structure
    public $table_structure = [
        "primaryKeyname"    => "id",
        "foreignKeyname"    => "%s_id"
    ];

    private static $_onQuery = null;

/*******************************************************************************/

    /**
     * Constructor & set the table structure
     *
     * @param PDO    $pdo            - The PDO connection
     * @param string $primaryKeyName - Structure: table primary. If its an array, it must be the structure
     * @param string $foreignKeyName - Structure: table foreignKeyName.
     *                  It can be like %s_id where %s is the table name
     */
    public function __construct(PDO $pdo, $primaryKeyName = "id", $foreignKeyName = "%s_id") 
    {
        $this->pdo = $pdo;
        $this->setStructure($primaryKeyName, $foreignKeyName);
    }

    /**
     * Define the working table and create a new instance
     *
     * @param  string   $tableName - Table name
     * @param  string   $alias     - The table alias name
     * @return $this
     */
    public function table($tableName, $alias = "")
    {
        $instance = clone($this);
        $instance->table_name = $tableName;
        $instance->table_token = $tableName;
        $instance->setTableAlias($alias);
        $instance->reset();
        return $instance;        
    }

    /**
     * Return the name of the table
     * @return string
     */
    public function getTablename(){
        return $this->table_name;
    }
    
    /**
     * Set the table alias
     *
     * @param string $alias
     * @return $this
     */
    public function setTableAlias($alias)
    {
        $this->table_alias = $alias;
        return $this;
    }

    public function getTableAlias()
    {
        return $this->table_alias;
    }
    
    /**
     * 
     * @param string $primaryKeyName - the primary key, ie: id
     * @param string $foreignKeyName - the foreign key as a pattern: %s_id, 
     *                                  where %s will be substituted with the table name
     * @return \$this
     */
    public function setStructure($primaryKeyName = "id", $foreignKeyName = "%s_id")
    {
        $this->table_structure = [
            "primaryKeyname" => $primaryKeyName,
            "foreignKeyname" => $foreignKeyName
        ];
        return $this;
    }
    
    /**
     * Return the table stucture
     * @return Array
     */
    public function getStructure()
    {
        return $this->table_structure;
    }
    
    /**
     * Get the primary key name
     * @return string
     */
    public function getPrimaryKeyname()
    {
        return $this->formatKeyname($this->table_structure["primaryKeyname"], $this->table_name);
    }
    
    /**
     * Get foreign key name
     * @return string
     */
    public function getForeignKeyname()
    {
        return $this->formatKeyname($this->table_structure["foreignKeyname"], $this->table_name);
    }
    
    /**
     * Return if the entry is of a single row
     * 
     * @return bool
     */
    public function isSingleRow()
    {
        return $this->is_single;
    }
    
/*******************************************************************************/
    /**
     * To execute a raw query
     * 
     * @param string    $query
     * @param Array     $parameters
     * @param bool      $return_as_pdo_stmt - true, it will return the PDOStatement
     *                                       false, it will return $this, which can be used for chaining
     *                                              or access the properties of the results
     * @return $this | PDOStatement
     */
    public function query($query, Array $parameters = [], $return_as_pdo_stmt = false)
    {
        $this->sql_parameters = $parameters;
        $this->sql_query = $query;

    
        $_stime = microtime(true);
        $this->pdo_stmt = $this->pdo->prepare($query);
        $this->pdo_executed = $this->pdo_stmt->execute($parameters);
        $_time = microtime(true) - $_stime;

        // query profiler
        if (! isset($this->query_profiler["total_time"])){
            $this->query_profiler["total_time"] = 0;
        }
        $this->query_profiler[] = [
            "query"         => $query,
            "params"        => $parameters,
            "affected_rows" => $this->rowCount(),
            "time"          => $_time
        ];
        $this->query_profiler["total_time"] = $this->query_profiler["total_time"] + $_time;
        
        if(is_array(self::$_onQuery)){
            call_user_func([self::$_onQuery[0], self::$_onQuery[1]], $query, $parameters);
        }else if(self::$_onQuery){
            call_user_func(self::$_onQuery, $query, $parameters);
        }

        if ($return_as_pdo_stmt) {
            return $this->pdo_stmt;
        } else {
            $this->is_fluent_query = true;
            return $this;
        }
    }
    
    /**
     * Return the number of affected row by the last statement
     *
     * @return int
     */
    public function rowCount()
    {
        return ($this->pdo_executed == true) ? $this->pdo_stmt->rowCount() : 0;
    }


/*------------------------------------------------------------------------------
                                Querying
*-----------------------------------------------------------------------------*/
    /**
     * To get all rows and create their instances
     * Use the query builder to build the where clause or $this->query with select
     * @return \Array
     */
    public function get()
    {
        if($this->is_fluent_query && $this->pdo_stmt == null){
            $this->query($this->getSelectQuery(), $this->getWhereParameters());
        }
        
        if ($this->pdo_executed == true) {
            $this->_data = $this->pdo_stmt->fetchAll(PDO::FETCH_ASSOC);
            $_objdata = [];
            foreach ($this->_data as $i => $row) {
                $_objdata[] = (object)$row;
            }

            $this->reset();
            return $_objdata;
        }
        return false;
    }

    public function debug(){
        if($this->is_fluent_query && $this->pdo_stmt == null){
            $this->sql_parameters = $this->getWhereParameters();
            $this->sql_query = $this->getSelectQuery();
            $this->pdo_stmt = $this->pdo->prepare($this->sql_query);
        }
        $this->pdo_stmt->debugDumpParams();
    }
    
    /**
     * Return one row
     *
     * @return $this
     */
    public function first()
    {
        $this->limit(1);
        $findAll = $this->get();
        return $findAll ? $findAll[0] : [];
    }
    

    /**
     * Create the select clause
     *
     * @param  mixed    $expr  - the column to select. Can be string or array of fields
     * @param  string   $alias - an alias to the column
     * @return $this
     */
    public function select($columns = "*", $alias = null)
    {
        $this->is_fluent_query = true;

        if(!$columns){
            $this->select_fields = [];
        }

        if ($alias && !is_array($columns)){
            $columns .= " AS {$alias} ";
        }

        if(is_array($columns)){
            $this->select_fields = array_merge($this->select_fields, $columns);
        } else {
            $this->select_fields[] = $columns;
        }

        return $this;
    }

    /**
     * Add where condition, more calls appends with AND
     *
     * @param string condition possibly containing ? or :name
     * @param mixed array accepted by PDOStatement::execute or a scalar value
     * @param mixed ...
     * @return $this
     */
    public function where($condition, $parameters = [])
    {
        $this->is_fluent_query = true;
        
        // By default the and_or_operator and wrap operator is AND, 
        if ($this->wrap_open || ! $this->and_or_operator) {
            $this->_and();
        }

        // where(array("column1" => 1, "column2 > ?" => 2))
        if (is_array($condition)) {
            foreach ($condition as $key => $val) {
                $this->where($key, $val);
            }
            return $this;
        }

        $args = func_num_args();
        if ($args != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", array(1, 2))
            if ($args != 2 || !is_array($parameters)) { // where("column < ? OR column > ?", 1, 2)
                $parameters = func_get_args();
                array_shift($parameters);
            }
        } else if (!is_array($parameters)) {//where(colum,value) => colum=value
            $condition .= " = ?";
            $parameters = [$parameters];
        } else if (is_array($parameters)) { // where("column", array(1, 2)) => column IN (?,?)
            $placeholders = $this->makePlaceholders(count($parameters));
            $condition = "({$condition} IN ({$placeholders}))";
        }

        $this->where_conditions[] = [
            "STATEMENT"   => $condition,
            "PARAMS"      => $parameters,
            "OPERATOR"    => $this->and_or_operator
        ];

        // Reset the where operator to AND. To use OR, you must call _or()
        $this->_and();
        
        return $this;
    }

    /**
     * Create an AND operator in the where clause
     * 
     * @return $this
     */
    public function _and() 
    {
        if ($this->wrap_open) {
            $this->where_conditions[] = self::OPERATOR_AND;
            $this->last_wrap_position = count($this->where_conditions);
            $this->wrap_open = false;
        } else {
            $this->and_or_operator = self::OPERATOR_AND;
        }
        return $this;
    }

    
    /**
     * Create an OR operator in the where clause
     * 
     * @return $this
     */    
    public function _or() 
    {
        if ($this->wrap_open) {
            $this->where_conditions[] = self::OPERATOR_OR;
            $this->last_wrap_position = count($this->where_conditions);
            $this->wrap_open = false;
        } else {
            $this->and_or_operator = self::OPERATOR_OR;
        }
        return $this;
    }
    
    /**
     * To group multiple where clauses together.  
     * 
     * @return $this
     */
    public function wrap()
    {
        $this->wrap_open = true;
        
        $spliced = array_splice($this->where_conditions, $this->last_wrap_position, count($this->where_conditions), "(");
        $this->where_conditions = array_merge($this->where_conditions, $spliced);

        array_push($this->where_conditions,")");
        $this->last_wrap_position = count($this->where_conditions);

        return $this;
    }
    
    /**
     * Where Primary key
     *
     * @param  int  $id
     * @return $this
     */
    public function wherePK($id)
    {
        return $this->where($this->getPrimaryKeyname(), $id);
    }

    /**
     * WHERE $columName != $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereNot($columnName, $value)
    {
        return $this->where("$columnName != ?", $value);
    }

    /**
     * WHERE $columName LIKE $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereLike($columnName, $value)
    {
        return $this->where("$columnName LIKE ?", $value);
    }

    /**
     * WHERE $columName NOT LIKE $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereNotLike($columnName, $value)
    {
        return $this->where("$columnName NOT LIKE ?", $value);
    }

    /**
     * WHERE $columName > $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereGt($columnName, $value)
    {
        return $this->where("$columnName > ?", $value);
    }

    /**
     * WHERE $columName >= $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereGte($columnName, $value)
    {
        return $this->where("$columnName >= ?", $value);
    }

    /**
     * WHERE $columName < $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereLt($columnName, $value)
    {
        return $this->where("$columnName < ?", $value);
    }

    /**
     * WHERE $columName <= $value
     *
     * @param  string   $columnName
     * @param  mixed    $value
     * @return $this
     */
    public function whereLte($columnName, $value)
    {
        return $this->where("$columnName <= ?", $value);
    }

    /**
     * WHERE $columName IN (?,?,?,...)
     *
     * @param  string   $columnName
     * @param  Array    $value
     * @return $this
     */
    public function whereIn($columnName, Array $values)
    {
        return $this->where($columnName,$values);
    }
    
    /**
     * WHERE $columName NOT IN (?,?,?,...)
     *
     * @param  string   $columnName
     * @param  Array    $value
     * @return $this
     */
    public function whereNotIn($columnName, Array $values)
    {
        $placeholders = $this->makePlaceholders(count($values));

        return $this->where("({$columnName} NOT IN ({$placeholders}))", $values);
    }

    /**
     * WHERE $columName IS NULL
     *
     * @param  string   $columnName
     * @return $this
     */
    public function whereNull($columnName)
    {
        return $this->where("({$columnName} IS NULL)");
    }

    /**
     * WHERE $columName IS NOT NULL
     *
     * @param  string   $columnName
     * @return $this
     */
    public function whereNotNull($columnName)
    {
        return $this->where("({$columnName} IS NOT NULL)");
    }

    
    public function having($statement, $operator = self::OPERATOR_AND) 
    {
        $this->is_fluent_query = true;
        $this->having[] = [
            "STATEMENT"   => $statement,
            "OPERATOR"    => $operator
        ];
        return $this;        
    }
    
    /**
     * ORDER BY $columnName (ASC | DESC)
     *
     * @param  string   $columnName - The name of the colum or an expression
     * @param  string   $ordering   (DESC | ASC)
     * @return $this 
     */
    public function orderBy($columnName, $ordering = "")
    {
        $this->is_fluent_query = true;
        $this->order_by[] = "{$columnName} {$ordering}";
        return $this;
    }

    /**
     * GROUP BY $columnName
     *
     * @param  string   $columnName
     * @return $this
     */
    public function groupBy($columnName)
    {
        $this->is_fluent_query = true;
        $this->group_by[] = $columnName;
        return $this;
    }

    
    /**
     * LIMIT $limit
     *
     * @param  int      $limit
     * @param  int      $offset
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->is_fluent_query = true;
        $this->limit = $limit;
        
        if($offset){
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Return the limit
     * 
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }
    
    /**
     * OFFSET $offset
     *
     * @param  int      $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->is_fluent_query = true;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Return the offset
     * 
     * @return type
     */
    public function getOffset()
    {
        return $this->offset;
    }
    

    /**
     * Build a join
     *
     * @param  type     $table         - The table name
     * @param  string   $constraint    -> id = profile.user_id
     * @param  string   $table_alias   - The alias of the table name
     * @param  string   $join_operator - LEFT | INNER | etc...
     * @return $this 
     */
    public function join($table, $constraint, $table_alias = "", $join_operator = "")
    {
        $this->is_fluent_query = true;

        if($table instanceof MoloOrm){
            $table = $table->table_name;
        }
        $join  = $join_operator ? "{$join_operator} " : "";
        $join .= "JOIN {$table} ";
        $join .= $table_alias ? "AS {$table_alias} " : "";
        $join .= "ON {$constraint}";
        $this->join_sources[] = $join;
        return $this;
    }

    /**
     * Create a left join
     *
     * @param  string   $table
     * @param  string   $constraint
     * @param  string   $table_alias
     * @return $this 
     */
    public function leftJoin($table, $constraint, $table_alias=null)
    {
        return $this->join($table, $constraint, $table_alias,"LEFT");
    }


    /**
     * Return the buit select query
     *
     * @return string
     */
    public function getSelectQuery()
    {
        if (!count($this->select_fields)) {
            $this->select("*");
        }

        $query  = "SELECT ";
        $query .= implode(", ", $this->prepareColumns($this->select_fields));
        $query .= " FROM {$this->table_name}".($this->table_alias ? " AS {$this->table_alias}" : "");
        if(count($this->join_sources)){
            $query .= (" ").implode(" ",$this->join_sources);
        }
            $query .= $this->getWhereString(); // WHERE
        if (count($this->group_by)){
            $query .= " GROUP BY " . implode(", ", array_unique($this->group_by));
        }
        if (count($this->order_by)){
            $query .= " ORDER BY " . implode(", ", array_unique($this->order_by));
        }
            $query .= $this->getHavingString(); // HAVING
        if ($this->limit){
            $query .= " LIMIT " . $this->limit;
        }
        if ($this->offset){
            $query .= " OFFSET " . $this->offset;
        }
        return $query;
    }

    /**
     * Prepare columns to include the table alias name
     * @param array $columns
     * @return array
     */
    private function prepareColumns(Array $columns){
        if (! $this->table_alias) {
            return $columns;
        }
        
        $newColumns = [];
        foreach ($columns as $column) {
            if (strpos($column, ",")) {
                $newColumns = array_merge($this->prepareColumns(explode(",", $column)), $newColumns);
            } else if (strpos($column, ".") == false && strpos(strtoupper($column), "NULL") == false) {
                $column = trim($column);
                if (preg_match("/^[0-9]/", $column)) {
                    $newColumns[] = trim($column);
                } else {
                    $newColumns[] = $this->table_alias.".{$column}";
                }
            } else {
                $newColumns[] = trim($column);
            }
        }
        return $newColumns;
    }
    
    /**
     * Build the WHERE clause(s)
     *
     * @return string
     */
    protected function getWhereString()
    {
        // If there are no WHERE clauses, return empty string
        if (!count($this->where_conditions)) {
            return " WHERE 1";
        } 

        $where_condition = "";
        $last_condition = "";

        foreach ($this->where_conditions as $condition) {
            if (is_array($condition)) {
                if ($where_condition && $last_condition != "(" && !preg_match("/\)\s+(OR|AND)\s+$/i", $where_condition)) {
                    $where_condition .= $condition["OPERATOR"];
                }
                $where_condition .= $condition["STATEMENT"];
                $this->where_parameters = array_merge($this->where_parameters, $condition["PARAMS"]);
            } else {
                $where_condition .= $condition;
            }
            $last_condition = $condition;
        }

        return " WHERE {$where_condition}" ;
    }
    
    /**
     * Return the HAVING clause
     * 
     * @return string
     */
    protected function getHavingString()
    {
        // If there are no WHERE clauses, return empty string
        if (!count($this->having)) {
            return "";
        } 

        $having_condition = "";

        foreach ($this->having as $condition) {
            if (is_array($condition)) {
                if ($having_condition && !preg_match("/\)\s+(OR|AND)\s+$/i", $having_condition)) {
                    $having_condition .= $condition["OPERATOR"];
                }
                $having_condition .= $condition["STATEMENT"];
            } else {
                $having_condition .= $condition;
            }
        }
        return " HAVING {$having_condition}" ;        
    }

    /**
     * Return the values to be bound for where
     *
     * @return Array
     */
    protected function getWhereParameters()
    {
        return $this->where_parameters;
    }

    /**
      * Detect if its a single row instance and reset it to PK
      *
      * @return $this 
      */
    protected function setSingleWhere()
    {
        if ($this->is_single) {
            $this->resetWhere();
            $this->wherePK($this->getPK());
        }
        return $this;
    }

    /**
      * Reset the where
      *
      * @return $this 
      */
    protected function resetWhere()
    {
        $this->where_conditions = [];
        $this->where_parameters = [];
        return $this;
    }  
    
    
/*------------------------------------------------------------------------------
                                Insert
*-----------------------------------------------------------------------------*/    
    /**
     * Insert new rows
     * $data can be 2 dimensional to add a bulk insert
     * If a single row is inserted, it will return it's row instance
     *
     * @param  array    $data - data to populate
     * @return $this 
     */
    public function insert(Array $data)
    {
        $insert_values = [];
        $question_marks = [];

        // check if the data is multi dimention for bulk insert
        $multi = (count($data) != count($data,COUNT_RECURSIVE));

        $datafield = array_keys( $multi ? $data[0] : $data);

        if ($multi) {
            foreach ($data as $d) {
                $question_marks[] = '('  . $this->makePlaceholders(count($d)) . ')';
                $insert_values = array_merge($insert_values, array_values($d));
            }
        } else {
            $question_marks[] = '('  . $this->makePlaceholders(count($data)) . ')';
            $insert_values = array_values($data);
        }

        $sql = "INSERT INTO {$this->table_name} (" . implode(",", $datafield ) . ") ";
        $sql .= "VALUES " . implode(',', $question_marks);

        $this->query($sql,$insert_values);
                
        $rowCount = $this->rowCount();
        // On single element return the object
        if ($rowCount == 1) {
            return $this->pdo->lastInsertId();
        }
        return $rowCount;
    }

/*------------------------------------------------------------------------------
                                Updating
*-----------------------------------------------------------------------------*/    
    /**
      * Update entries
      * Use the query builder to create the where clause
      *
      * @param Array the data to update
      * @return int - total affected rows
      */
    public function update(Array $data = null)
    {
        $this->setSingleWhere();

        if (! is_null($data)) {
            $this->set($data);
        }

        // Make sure we remove the primary key
        unset($this->_dirty_fields[$this->getPrimaryKeyname()]);
        
        $values = array_values($this->_dirty_fields);
        $field_list = [];

        if (count($values) == 0){
            return false;
        }

        foreach (array_keys($this->_dirty_fields) as $key) {
            $field_list[] = "{$key} = ?";
        }

        $query  = "UPDATE {$this->table_name} SET ";
        $query .= implode(", ",$field_list);
        $query .= $this->getWhereString();

        $values = array_merge($values, $this->getWhereParameters());

        $this->query($query, $values);
        
        // Return the SQL Query
        $this->_dirty_fields = [];
        return $this->rowCount();
    }

/*------------------------------------------------------------------------------
                                Delete
*-----------------------------------------------------------------------------*/    
    /**
     * Delete rows
     * Use the query builder to create the where clause
     * @parama bool $deleteAlls = When there is no where condition, setting to true will delete all
     * @return int - total affected rows
     */
    public function delete($deleteAll = false)
    {
        $this->setSingleWhere();
        
        if (count($this->where_conditions)) {
            $query  = "DELETE FROM {$this->table_name}";
            $query .= $this->getWhereString();
            $this->query($query, $this->getWhereParameters());           
        } else {
            if ($deleteAll) {
                $query  = "DELETE FROM {$this->table_name}";
                $this->query($query);                
            } else {
                return false;
            }
        }

        // Return the SQL Query
        return $this->rowCount();
    }
    
/*------------------------------------------------------------------------------
                                Set & Save
*-----------------------------------------------------------------------------*/
    /**
     * To set data for update or insert
     * $key can be an array for mass set
     *
     * @param  mixed    $key
     * @param  mixed    $value
     * @return $this 
     */
    public function set($key, $value = null)
    {
        if(is_array($key)) {
            foreach ($key as $keyKey => $keyValue) {
                $this->set($keyKey, $keyValue);
            }
        }  else {
            if( $key != $this->getPrimaryKeyname()) {
                $this->_data[$key] = $value;
                $this->_dirty_fields[$key] = $value;                
            }
        }
        return $this;
    }

    /**
     * Save, a shortcut to update() or insert().
     * 
     * @return mixed 
     */
    public function save() 
    {
        if ($this->is_single || count($this->where_conditions)) {
            return $this->update();
        } else {
            return $this->insert($this->_dirty_fields);
        }
    }    


/*------------------------------------------------------------------------------
                                AGGREGATION
*-----------------------------------------------------------------------------*/
    
    /**
     * Return the aggregate count of column
     *
     * @param  string $column - the column name
     * @return double
     */
    public function count($column="*")
    {
        return $this->aggregate("COUNT({$column})");
    }

    /**
     * Return the aggregate max count of column
     *
     * @param  string $column - the column name
     * @return double
     */
    public function max($column)
    {
        return $this->aggregate("MAX({$column})");
    }


    /**
     * Return the aggregate min count of column
     *
     * @param  string $column - the column name
     * @return double
     */
    public function min($column)
    {
        return $this->aggregate("MIN({$column})");
    }

    /**
     * Return the aggregate sum count of column
     *
     * @param  string $column - the column name
     * @return double
     */
    public function sum($column)
    {
        return $this->aggregate("SUM({$column})");
    }

    /**
     * Return the aggregate average count of column
     *
     * @param  string $column - the column name
     * @return double
     */
    public function avg($column)
    {
        return $this->aggregate("AVG({$column})");
    }

    /**
     *
     * @param  string $fn - The function to use for the aggregation
     * @return double
     */
    public function aggregate($fn)
    {
        $this->select($fn, 'count');
        $result = $this->first();
        return ($result !== false && isset($result->count)) ? $result->count : 0;
    }

/*------------------------------------------------------------------------------
                                Access single entry data
*-----------------------------------------------------------------------------*/
    /**
     * Return the primary key
     *
     * @return int
     */
    public function getPK()
    {
        return $this->get($this->getPrimaryKeyname());
    }

    /**
     * Return the raw data of this single instance
     *
     * @return Array
     */
    public function toArray()
    {
        return $this->_data;
    }
    
    public static function setOnQuery($callback){
        self::$_onQuery = $callback;
    }

    public static function getOnQuery(){
        return self::$_onQuery;
    }

/*******************************************************************************/
// Utilities methods

    /**
     * Reset fields
     *
     * @return $this 
     */
    public function reset()
    {
        $this->where_parameters = [];
        $this->select_fields = [];
        $this->join_sources = [];
        $this->where_conditions = [];
        $this->limit = null;
        $this->offset = null;
        $this->order_by = [];
        $this->group_by = [];
        $this->_dirty_fields = [];
        $this->is_fluent_query = true;
        $this->and_or_operator = self::OPERATOR_AND;
        $this->having = [];
        $this->wrap_open = false;
        $this->last_wrap_position = 0;
        $this->debug_sql_query = false;
        $this->pdo_stmt = null;
        $this->is_single = false;
        return $this;
    }

    /**
     * Return a YYYY-MM-DD HH:II:SS date format
     * 
     * @param string $datetime - An english textual datetime description
     *          now, yesterday, 3 days ago, +1 week
     *          http://php.net/manual/en/function.strtotime.php
     * @return string YYYY-MM-DD HH:II:SS
     */    
    public static function NOW($datetime = "now")
    {
        return (new DateTime($datetime ?: "now"))->format("Y-m-d H:i:s");
    }


/*******************************************************************************/
// Query Debugger
    /**
     * Get the SQL Query with 
     * 
     * @return string 
     */
    public function getSqlQuery()
    {
        return $this->sql_query;
    }
    
    /**
     * Return the parameters of the SQL
     * 
     * @return array
     */
    public function getSqlParameters()
    {
        return $this->sql_parameters;
    }
    
    /**
     * To profile all queries that have been executed
     *
     * @return Array
     */
    public function getQueryProfiler()
    {
        return $this->query_profiler;
    }
/*******************************************************************************/
    /**
     * Return a string containing the given number of question marks,
     * separated by commas. Eg "?, ?, ?"
     *
     * @param int - total of placeholder to inser
     * @return string
     */
    protected function makePlaceholders($number_of_placeholders=1)
    {
        return implode(", ", array_fill(0, $number_of_placeholders, "?"));
    }

    /**
     * Format the table{Primary|Foreign}KeyName
     *
     * @param  string $pattern
     * @param  string $tablename
     * @return string
     */
    protected function formatKeyname($pattern, $tablename)
    {
       return sprintf($pattern,$tablename);
    }

    /**
     * To create a string that will be used as key for the relationship
     *
     * @param  type   $key
     * @param  type   $suffix
     * @return string
     */
    private function tokenize($key, $suffix = "")
    {
        return  $this->table_token.":$key:$suffix";
    }

    public function __clone()
    {
    }
    
    public function __toString()
    {
        return $this->is_single ? $this->getPK() : $this->table_name;
    }    
}
