<?php
namespace exts;
use PDO;
use exts\MoloOrm;

class NewOrm extends MoloOrm{
    /**
     * get result by paginate
     * @return array paginate result
     */
    public function paginate(){
        $req = request();
        $data = $req->all;

        $page = intval(all('page', 1));
        $page = max(1, $page);

        $per_page = all('per_page', 1);
        $per_page = max(15, $per_page);

        $obj = clone $this;
        $total = $obj->count();

        $offset = ($page-1)*$per_page;
        $sele = $this->offset($offset)->limit($per_page);
        $list = $sele->get();

        return [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'from' => $offset,
            'to' => $offset+count($list),
            'data' => $list
        ];
    }
}

class DB{

    /**
     * get database connection
     * @param  string $dbkey table key config in config/databases.php
     * @return NewOrm        database instance
     */
    public static function connection($dbkey){
        $connConfig = config('databases.connections');

        if(!isset($connConfig[$dbkey])){
            throw new \Exception("database config: {$dbkey} not found.");
        }

        $config = $connConfig[$dbkey];
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $port = isset($config['port']) ? $config['port'] : '3306';

        $dbname = isset($config['database']) ? $config['database'] : 'test';
        $username = isset($config['username']) ? $config['username'] : 'root';
        $password = isset($config['password']) ? $config['password'] : '';

        $ext = isset($config['charset']) ? ';charset='.$config['charset'] : '';

        //create pdo instance
        $pdo = new PDO("mysql:host={$host};port={$port};dbname=$dbname{$ext}", $username, $password);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return new NewOrm($pdo);
    }

}